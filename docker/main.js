const express = require('express');
const path = require('path');
const mysql = require('mysql');
const dotenv = require('dotenv');
const request = require('request');

dotenv.config();

const app = express();
const PORT = 5000;

// MySQL connection setup
const db = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME
});

db.connect((err) => {
  if (err) {
    console.error('Error connecting to the database:', err);
    return;
  }
  console.log('Connected to the MySQL database');
});

// Middleware to parse JSON bodies
app.use(express.json());

// Define a route to serve the zip file
app.post('/', (req, res) => {
  const { serial, secret } = req.body;

  if (!serial || !secret) {
    return res.status(400).send('unauthorized');
  }

  const query = 'SELECT docker_download_status FROM devices WHERE serial = ? AND secret = ?';
  db.query(query, [serial, secret], (err, results) => {
    if (err) {
      console.error('Error querying the database:', err);
      return res.status(500).send('Error querying the database');
    }

    if (results.length === 0) {
      return res.status(401).send('unauthorized');
    }

    const { docker_download_status } = results[0];
    if (docker_download_status === 1) {
      const filePath = path.join(__dirname, 'content', 'docker_container.tar');
      res.download(filePath, 'docker_container.tar', (err) => {
        if (err) {
          console.error('Error sending file:', err);
          return res.status(500).send('Error sending file');
        }

        // Update the docker_download_status to 0 after successful download
        const updateQuery = 'UPDATE devices SET docker_download_status = 0 WHERE serial = ? AND secret = ?';
        db.query(updateQuery, [serial, secret], (updateErr) => {
          if (updateErr) {
            console.error('Error updating the database:', updateErr);
          }
        });
      });
    } else {
      res.status(403).send('no_download');
    }
  });
});

app.post('/docker_ping', (req, res) => {
  const { hostname } = req.body; //hostname is the tunnel hostname. Example: vl0igh-ip-150-135-165-16.philipehrbright.tech

  if (!hostname) {
    return res.status(400).send('unauthorized');
  }

  //Send a request to https://philipehrbright.tech/tunnelmole-connections?password={password} to get the list of connected tunnels
  //JSON parse the result, if the hostname is in the list, return 200, else return 403
  request.get(`https://philipehrbright.tech/tunnelmole-connections?password=${process.env.TUNNELMOLE_PASSWORD}`, (err, response, body) => {
    if (err) {
      console.error('Error querying the tunnelmole:', err);
      return res.status(500).send('Error querying the tunnelmole');
    }

    const connectedTunnels = JSON.parse(body);
    const isTunnelConnected = connectedTunnels.some(tunnel => tunnel.hostname === hostname);
    
    if (isTunnelConnected) {
      return res.status(200).send('connected');
    } else {
      return res.status(403).send('not_connected');
    }

  });

});

app.get('/docker_ping', (req, res) => {
  return res.send('This is a POST endpoint!');
});

app.get('/', (req, res) => {
  return res.send('This is a POST endpoint!');
});

// Start the server
app.listen(PORT, 'localhost', () => {
  console.log(`Server is running on port ${PORT}`);
  console.log(`http://localhost:${PORT}`);
});
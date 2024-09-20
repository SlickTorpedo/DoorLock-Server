const express = require('express');
const path = require('path');
const mysql = require('mysql');
const dotenv = require('dotenv');

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

app.get('/', (req, res) => {
    res.send('This is a POST endpoint only silly goose.');
});

// Start the server
app.listen(PORT, 'localhost', () => {
  console.log(`Server is running on port ${PORT}`);
});
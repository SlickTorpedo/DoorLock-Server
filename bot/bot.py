import os
import discord
from discord.ext import commands
import requests
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

# Get the admin secret and Discord token from environment variables
ADMIN_SECRET = os.getenv('ADMIN_SECRET')
DISCORD_TOKEN = os.getenv('DISCORD_TOKEN')

intents = discord.Intents.default()
intents.message_content = True

bot = commands.Bot(command_prefix='-', intents=intents)

@bot.command()
async def accept(ctx, serial: str, secret: str):
    # Define the URL for the registration
    url = 'https://registrar.philipehrbright.com/register/'

    # Data to be sent in the POST request
    data = {
        'serial': serial,
        'secret': secret,
        'new_secret': secret,  # Assuming new_secret is the same as secret
        'admin_secret': ADMIN_SECRET,
        'tunnel_url': ''  # Optional: Replace with actual tunnel URL, or leave blank
    }

    # Send the POST request
    try:
        response = requests.post(url, data=data)

        # Check if the request was successful
        if response.status_code == 201:
            await ctx.send("Device registered successfully.")
        else:
            await ctx.send(f"Failed to register device: {response.status_code}")
            try:
                error_details = response.json()
                await ctx.send(f"Error details: {error_details}")
            except ValueError:
                await ctx.send("No additional error details.")
    except requests.RequestException as e:
        await ctx.send(f"An error occurred: {e}")

bot.run(DISCORD_TOKEN)
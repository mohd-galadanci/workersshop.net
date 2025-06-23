
const https = require('https');

const token = process.env.DISCORD_BOT_TOKEN;

if (!token) {
    console.error('DISCORD_BOT_TOKEN not found in environment variables');
    process.exit(1);
}

const options = {
    hostname: 'discord.com',
    path: '/api/v10/users/@me',
    method: 'GET',
    headers: {
        'Authorization': `Bot ${token}`
    }
};

const req = https.request(options, (res) => {
    let data = '';
    
    res.on('data', (chunk) => {
        data += chunk;
    });
    
    res.on('end', () => {
        console.log('Response:', JSON.parse(data));
    });
});

req.on('error', (error) => {
    console.error('Error:', error);
});

req.end();


const { Client, GatewayIntentBits, SlashCommandBuilder, REST, Routes, EmbedBuilder } = require('discord.js');
const axios = require('axios');

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
        GatewayIntentBits.MessageContent
    ]
});

// Your Food Request System API base URL
const API_BASE_URL = process.env.API_BASE_URL || 'https://server1.workersshop.net';

// Slash commands
const commands = [
    new SlashCommandBuilder()
        .setName('request-food items')
        .setDescription('Submit a new food items request')
        .addStringOption(option =>
            option.setName('item')
                .setDescription('Food item you want to request')
                .setRequired(true)
        )
        .addStringOption(option =>
            option.setName('reason')
                .setDescription('Reason for the request')
                .setRequired(false)
        ),
    new SlashCommandBuilder()
        .setName('my-requests')
        .setDescription('View your food requests'),
    new SlashCommandBuilder()
        .setName('help')
        .setDescription('Get help with the Food Request System'),
];

// Store user tokens (in production, use a proper database)
const userTokens = new Map();

client.once('ready', async () => {
    console.log(`Discord bot ready! Logged in as ${client.user.tag}`);
    
    const rest = new REST({ version: '10' }).setToken(process.env.DISCORD_BOT_TOKEN);
    
    try {
        console.log('Started refreshing application (/) commands.');
        
        await rest.put(
            Routes.applicationCommands(client.user.id),
            { body: commands },
        );
        
        console.log('Successfully reloaded application (/) commands.');
    } catch (error) {
        console.error('Error registering commands:', error);
    }
});

client.on('interactionCreate', async interaction => {
    if (!interaction.isChatInputCommand()) return;

    const { commandName } = interaction;

    try {
        if (commandName === 'request-food') {
            const item = interaction.options.getString('item');
            const reason = interaction.options.getString('reason') || 'No reason provided';
            
            // For demo purposes, we'll create a simple request
            // In production, you'd need proper user authentication
            const embed = new EmbedBuilder()
                .setColor(0x0099FF)
                .setTitle('🍽️ Food Request Submitted')
                .addFields(
                    { name: 'Item', value: item, inline: true },
                    { name: 'Reason', value: reason, inline: true },
                    { name: 'Status', value: 'Pending', inline: true },
                    { name: 'Requested by', value: interaction.user.username, inline: true }
                )
                .setTimestamp();

            await interaction.reply({ 
                content: 'Your food request has been submitted!',
                embeds: [embed]
            });

        } else if (commandName === 'my-requests') {
            const embed = new EmbedBuilder()
                .setColor(0x00FF00)
                .setTitle('📋 Your Food Requests')
                .setDescription('Here are your recent food requests:')
                .addFields(
                    { name: '🍕 Pizza', value: 'Status: Approved ✅', inline: false },
                    { name: '🥗 Salad', value: 'Status: Pending ⏳', inline: false },
                    { name: '☕ Coffee', value: 'Status: Denied ❌', inline: false }
                )
                .setTimestamp();

            await interaction.reply({ embeds: [embed] });

        } else if (commandName === 'help') {
            const embed = new EmbedBuilder()
                .setColor(0xFFFF00)
                .setTitle('🤖 workersshop Help')
                .setDescription('Available commands:')
                .addFields(
                    { name: '/request-food', value: 'Submit a new food request', inline: false },
                    { name: '/my-requests', value: 'View your food requests', inline: false },
                    { name: '/help', value: 'Show this help message', inline: false }
                )
                .setFooter({ text: 'workersshopt System Discord Bot' });

            await interaction.reply({ embeds: [embed] });
        }
    } catch (error) {
        console.error('Error handling command:', error);
        await interaction.reply({ 
            content: 'Sorry, there was an error processing your request.',
            ephemeral: true 
        });
    }
});

// Handle regular messages for natural language processing
client.on('messageCreate', message => {
    if (message.author.bot) return;

    const content = message.content.toLowerCase();
    
    if (content.includes('food') && content.includes('request')) {
        message.reply('👋 Hi! Use `/request-food` to submit a food request, or `/help` for more commands!');
    }
});

client.login(process.env.DISCORD_BOT_TOKEN);

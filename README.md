# WordPress Dual AI Assistant

A comprehensive WordPress plugin that integrates both text-based AI chatbot (using Anthropic Claude) and voice-based AI agent (using ElevenLabs) with an administrative dashboard for monitoring interactions.

## Features

- **Text Chat Integration**: Uses Anthropic's Claude API to provide intelligent text-based conversations
- **Voice Chat Integration**: Implements ElevenLabs' conversational voice AI with realistic voice interactions
- **Customizable Widget Placement**: Use shortcodes or automatic WooCommerce integration
- **Comprehensive Dashboard**: View interaction statistics and export reports
- **WooCommerce Integration**: Automatically adds AI assistants to product pages
- **Realistic Phone Call Experience**: Includes ringtone and voice feedback for natural interactions

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Node.js 16+ (for development only)
- NPM 8+ (for development only)
- Anthropic API key
- ElevenLabs API key and Agent ID

## Installation

### Manual Installation

1. Download the latest release zip file
2. Log in to your WordPress dashboard
3. Navigate to Plugins → Add New
4. Click "Upload Plugin" and select the downloaded zip file
5. Activate the plugin after installation

### Development Installation

1. Clone the repository to your local machine
2. Navigate to the plugin directory
3. Install dependencies:

```bash
npm install
```

4. Build the plugin assets:

```bash
npm run build
```

5. Copy the files to your WordPress plugins directory or create a symlink

## Configuration

1. After activating the plugin, navigate to "Dual AI Assistant" in your WordPress admin menu
2. Configure your API credentials:
   - Anthropic API key for text chat functionality
   - ElevenLabs API key and Agent ID for voice chat functionality
3. Customize the welcome message and other settings

## Usage

### Shortcodes

The plugin provides three shortcodes for adding AI assistants to your site:

```
[dual_ai_text_chat]
[dual_ai_voice_chat]
[dual_ai_chat_buttons]
```

Each shortcode accepts parameters for customization:

- Text Chat: `[dual_ai_text_chat title="Chat with our AI" product_id="123"]`
- Voice Chat: `[dual_ai_voice_chat title="Voice Assistant" product_id="123"]`
- Chat Buttons: `[dual_ai_chat_buttons text="true" voice="true" position="bottom-right"]`

### WooCommerce Integration

If WooCommerce is active, the plugin can automatically add chat interfaces to product pages:

1. Go to the plugin settings
2. Under General Settings, select the desired display option for product pages

### Reporting

The admin dashboard provides comprehensive reporting:

1. Navigate to Dual AI Assistant → Reports
2. Use date filters to analyze interactions over specific periods
3. Export data to CSV for further analysis

## Development

### NPM Scripts

- `npm run dev`: Start development mode with auto-compilation
- `npm run build`: Build production assets
- `npm run lint`: Run ESLint on JavaScript files
- `npm run lint:fix`: Fix ESLint issues automatically

### File Structure

```
wp-dual-ai-assistant/
├── admin/               # Admin-specific files
├── api/                 # API integration classes
├── assets/              # Images, audio files
├── includes/            # Core plugin classes
├── public/              # Public-facing functionality
├── package.json         # NPM package configuration
├── webpack.config.js    # Webpack configuration
└── wp-dual-ai-assistant.php # Main plugin file
```

## Troubleshooting

### API Connection Issues

If you experience connection issues:

1. Verify your API keys are entered correctly
2. Use the "Test Connection" buttons in the settings to validate credentials
3. Check server outbound connections (some hosts restrict external API calls)

### Voice Chat Not Working

For voice chat issues:

1. Ensure the browser has microphone permissions
2. Verify the ElevenLabs Agent ID is correct
3. Test on different browsers (Chrome/Firefox recommended)

## Credits

- Icon designs from [Feather Icons](https://feathericons.com/)
- Uses [ElevenLabs Conversational AI SDK](https://elevenlabs.io/docs/conversational-ai/overview)
- Implements [Anthropic Claude API](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)

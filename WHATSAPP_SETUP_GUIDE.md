# WhatsApp Cloud API Setup Guide

This guide will help you set up WhatsApp Cloud API to send job notifications.

## Prerequisites

- Active Facebook/Meta account
- A phone number (can be your current number)
- Business account (can be created for free)

---

## Step 1: Create Meta Business Suite Account

1. Go to [Meta Business Suite](https://www.facebook.com/business/suite)
2. Click "Create Account"
3. Follow the setup wizard
4. Enter your business details (can be individual/freelancer)

---

## Step 2: Create WhatsApp Cloud API App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click "My Apps" → "Create App"
3. Select "Business" type
4. Fill in app details:
   - **App Display Name**: Upwork Job Agent
   - **App Contact Email**: habibahmed001@gmail.com
5. Click "Create App"

---

## Step 3: Add WhatsApp Product

1. In your app dashboard, find "Add Products" section
2. Click "Set Up" for **WhatsApp**
3. Choose your phone number: +923228594463
4. Follow the verification process (Meta will send a code via SMS)

---

## Step 4: Get Credentials

After setting up WhatsApp, you'll find these in your app dashboard:

### Phone ID
1. Go to **WhatsApp** → **Configuration**
2. Copy the **Phone Number ID**
3. This is your `WHATSAPP_PHONE_ID`

### Access Token
1. In the same **WhatsApp Configuration** page
2. Scroll to **Access Tokens**
3. Click **Generate Token**
4. Copy the token (keep it secure!)
5. This is your `WHATSAPP_ACCESS_TOKEN`

### Business Account ID
1. Go to **App Settings** → **Basic**
2. Copy the **App ID**
3. This is your `WHATSAPP_BUSINESS_ACCOUNT_ID`

---

## Step 5: Test Your Setup

Before integrating with the application, test your WhatsApp setup:

### Send Test Message (API Test)

Use this curl command to test:

```bash
curl -X POST 'https://graph.facebook.com/v18.0/YOUR_PHONE_ID/messages' \
  -H 'Authorization: Bearer YOUR_ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "messaging_product": "whatsapp",
    "to": "923228594463",
    "type": "template",
    "template": {
      "name": "hello_world",
      "language": {"code": "en_US"}
    }
  }'
```

Replace:
- `YOUR_PHONE_ID` with your Phone ID
- `YOUR_ACCESS_TOKEN` with your Access Token
- `923228594463` with your number (without +)

### Send Test Message (Custom Text)

After 24 hours of using templates, you can send custom messages:

```bash
curl -X POST 'https://graph.facebook.com/v18.0/YOUR_PHONE_ID/messages' \
  -H 'Authorization: Bearer YOUR_ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "messaging_product": "whatsapp",
    "to": "923228594463",
    "type": "text",
    "text": {
      "body": "🔥 Test message from Upwork Job Agent"
    }
  }'
```

---

## Important Notes

### Rate Limits
- **Tier 1** (new numbers): 1,000 conversations per day
- Each message to a new number starts a new conversation
- Multiple messages to same number = 1 conversation

### Free Tier vs Paid
- **Free**: 1,000 conversations/month
- **Paid**: Varies by volume

### 24-Hour Rule
- New business-initiated conversations require **template messages** for first 24 hours
- After 24 hours, you can send custom messages
- User-initiated conversations allow custom messages immediately

---

## Alternative: Twilio (If Meta Issues)

If you face issues with Meta setup, you can use Twilio:

1. Sign up at [Twilio Console](https://www.twilio.com/console)
2. Get WhatsApp Sandbox number
3. Join sandbox with your phone
4. Use Twilio API for sending messages

---

## Credentials for .env File

Once you have everything, add to your `.env`:

```env
WHATSAPP_PHONE_ID=your_phone_id_here
WHATSAPP_ACCESS_TOKEN=your_access_token_here
WHATSAPP_PHONE_NUMBER=+923228594463
WHATSAPP_BUSINESS_ACCOUNT_ID=your_ba_id_here
```

---

## Troubleshooting

### Issue: "Permission denied"
- Check your Access Token has `whatsapp_business_messaging` permission
- Regenerate token if expired

### Issue: "Number not linked"
- Ensure phone number is verified in Meta Business Suite
- Check number is in the same region as business account

### Issue: "Rate limit exceeded"
- Wait for daily reset (midnight Pacific time)
- Consider upgrading to paid tier

---

*Need help? Check [Meta Developer Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)*

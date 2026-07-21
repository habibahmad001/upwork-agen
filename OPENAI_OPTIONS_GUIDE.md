# OpenAI API Options & Setup Guide

## Current Situation

**Important**: OpenAI API has **no free tier**. However, for development, we have several options.

---

## Option 1: OpenAI Paid API (Production)

### Pricing
- **GPT-4o-mini** (recommended for this project):
  - Input: $0.150 / 1M tokens
  - Output: $0.600 / 1M tokens
- **Estimated monthly cost**: $5-15 (depending on job volume)

### How Much Will This Cost?

For Upwork Job Monitor:
- Average job description: ~500 tokens (input)
- AI response: ~300 tokens (output)
- **Cost per job**: ~$0.0002
- **100 jobs/day**: ~$0.02/day = **$0.60/month**
- **1000 jobs/day**: ~$0.20/day = **$6/month**

### Setup Guide

1. Go to [platform.openai.com](https://platform.openai.com/)
2. Sign up with your email
3. Verify your email address
4. Add a payment method (required for API access)
5. Go to **Settings** → **API Keys**
6. Click "Create new secret key"
7. Copy the key (starts with `sk-`)

### Add to .env

```env
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini
```

---

## Option 2: Free Alternatives (Development)

### Groq (Recommended for Development)
- **Free tier available**
- Very fast inference
- Llama 3, Mixtral models available

**Setup**:
1. Go to [console.groq.com](https://console.groq.com/)
2. Create account
3. Get API key from **Keys** section
4. Free: ~14,000 requests/day

```env
OPENAI_API_KEY=gsk_your-groq-key
OPENAI_MODEL=llama3-70b-8192
GROQ_API_BASE=https://api.groq.com/openai/v1
```

### Hugging Face Inference API
- Free for some models
- Rate limited but functional

### Local Ollama (Free, Requires Hardware)
- Run models locally on your machine
- Completely free
- Requires good CPU/GPU

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Run a model
ollama run llama3
```

---

## Option 3: Mock Service (Development Only)

For initial development, we can create a mock scoring service:
- Returns realistic-looking scores
- No API calls needed
- Perfect for testing UI and workflows

**Will be implemented by default** - you can add real API later.

---

## Recommendation

### Phase 1: Development (Now)
✅ Use **Mock Service** - Built into the application

### Phase 2: Testing (Optional)
✅ Use **Groq Free Tier** - Real AI, free for development

### Phase 3: Production
✅ Use **OpenAI GPT-4o-mini** - Best quality, low cost ($5-15/month)

---

## How We'll Implement

The application will support **multiple AI providers**:

```php
// config/ai.php
'provider' => env('AI_PROVIDER', 'mock'), // mock, openai, groq
'providers' => [
    'mock' => [
        'enabled' => true,
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => 'gpt-4o-mini',
        'base_url' => 'https://api.openai.com/v1',
    ],
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => 'llama3-70b-8192',
        'base_url' => 'https://api.groq.com/openai/v1',
    ],
],
```

You can switch between providers anytime via `.env`:
```env
AI_PROVIDER=mock      # Development
AI_PROVIDER=groq      # Free testing
AI_PROVIDER=openai    # Production
```

---

## Current Decision

**For Milestone 1-7**: We'll use **Mock Service**
**For Production**: You can add OpenAI or Groq when ready

The application will work perfectly without an API key during development!

---

*When ready for production, simply update AI_PROVIDER and add your API key.*

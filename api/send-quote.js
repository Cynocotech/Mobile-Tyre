/**
 * Vercel serverless function: forwards quote form to Telegram bot.
 * Set env vars in Vercel: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID
 */
module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(204).end();
  if (req.method !== 'POST') return res.status(405).json({ ok: false, error: 'Method not allowed' });

  const token = process.env.TELEGRAM_BOT_TOKEN;
  const chatId = process.env.TELEGRAM_CHAT_ID;
  if (!token || !chatId) {
    return res.status(500).json({ ok: false, error: 'Server config missing' });
  }

  const { vrm = '', location = '', mobile = '', car_make = '', car_model = '', tyre_size = '' } = req.body || {};
  const trim = (s) => String(s).trim();
  const text = [
    '🛞 New quote request',
    '🚗 VRM: ' + (trim(vrm) || '—'),
    '📍 Location: ' + (trim(location) || '—'),
    '📱 Mobile: ' + (trim(mobile) || '—'),
    '🏭 Make: ' + (trim(car_make) || '—'),
    '🚙 Model: ' + (trim(car_model) || '—'),
    '🛞 Tyre size: ' + (trim(tyre_size) || '—'),
  ].join('\n');

  try {
    const tgRes = await fetch(`https://api.telegram.org/bot${token}/sendMessage`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ chat_id: chatId, text }),
    });
    const data = await tgRes.json();
    if (!data.ok) {
      return res.status(400).json({ ok: false, error: data.description || 'Telegram error' });
    }
    return res.status(200).json({ ok: true });
  } catch (err) {
    return res.status(500).json({ ok: false, error: err.message });
  }
};

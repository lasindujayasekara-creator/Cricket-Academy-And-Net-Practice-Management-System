// Fetch API helper for PHP backend
const API = {
  async request(url, options = {}) {
    options.headers = { 'Content-Type': 'application/json', ...options.headers };
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok) {
      if (res.status === 401) { window.location.href = 'index.php'; return null; }
      throw new Error(data.message || 'Request failed.');
    }
    return data;
  },
  get(url)          { return this.request(url, { method: 'GET' }); },
  post(url, body)   { return this.request(url, { method: 'POST', body: JSON.stringify(body) }); },
  put(url, body)    { return this.request(url, { method: 'PUT', body: JSON.stringify(body) }); },
  delete(url)       { return this.request(url, { method: 'DELETE' }); }
};

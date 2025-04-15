# OAuth Proxy for REDAXO

A REDAXO addon that securely handles OAuth requests by automatically injecting client credentials (client_id and client_secret) into authentication requests, keeping sensitive credentials secure on the server side.

## Features

- Secure proxy for OAuth client credentials flow
- Server-side injection of client ID and client secret
- CORS-enabled for cross-domain requests
- Simple API for frontend applications
- Configurable OAuth provider URL and credentials
- Easy to implement in modern JavaScript applications

## Installation

1. Download the addon from GitHub
2. Extract to your REDAXO addons directory (`redaxo/src/addons/oauth_proxy`)
3. Install the addon through the REDAXO backend
4. Configure your OAuth settings

## Configuration

Navigate to the addon settings in your REDAXO backend:

1. Enter your OAuth provider URL (e.g., `https://auth.example.com/oauth/token`)
2. Enter your client ID
3. Enter your client secret
4. Save your settings

## Usage

The addon creates an API endpoint that can be accessed at:

```
https://your-site.com/index.php?rex-api-call=oauth_proxy
```

### Client Credentials Flow

This addon currently supports the client credentials grant type only. Here's how to use it:

```js
// Request an access token using client credentials
fetch("https://your-site.com/index.php?rex-api-call=oauth_proxy", {
    method: "POST",
    credentials: "include", // Important for cookie handling
    headers: { 
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
})
.then(response => {
    if (!response.ok) {
        throw new Error('Network response was not ok');
    }
    return response.json();
})
.then(data => {
    console.log("Access token received:", data.access_token);
    // Use the access token for API calls
})
.catch(error => {
    console.error("Error fetching token:", error);
});
```

### Using with Axios

```js
axios.post("https://your-site.com/index.php?rex-api-call=oauth_proxy", {}, {
  headers: {
    "Content-Type": "application/json",
    "Accept": "application/json"
  },
  withCredentials: true // Important for cookie handling
})
.then(response => {
  // Handle response
  const token = response.data.access_token;
  // Use token for authenticated requests
  axios.defaults.headers.common['Authorization'] = 
    `Bearer ${token}`;
})
.catch(error => {
  console.error("Error:", error);
});
```

### API Proxy Functionality

The addon now provides a full API proxy that can forward requests to external APIs while handling OAuth authentication automatically:

```
https://your-site.com/index.php?rex-api-call=proxy_endpoint&target=https://api.example.com/data
```

With this feature you can:
- Make requests to any API endpoint through the proxy
- The proxy automatically handles OAuth authentication and token refresh
- All headers and query parameters are forwarded to the target API
- CORS is handled automatically

#### Example: Proxying API Requests with Axios

```js
// Make a request to an external API through the proxy
axios({
  method: 'get',
  url: 'https://your-site.com/index.php?rex-api-call=proxy',
  params: {
    target: 'https://api.example.com/users', // Target API endpoint
    limit: 10,                               // Additional parameters for the API
    offset: 0
  },
  withCredentials: true // Important for maintaining session cookies
})
.then(...)
```

#### Example: Making POST Requests Through the Proxy

```js
// Make an oauth request agains the provider through the plugins oauth_proxy endpoint
axios({
  method: 'post',
  url: 'https://your-site.com/index.php?rex-api-call=oauth_proxy'
})
.then(...)
```

## How It Works

1. The client makes a POST request to the proxy endpoint
2. The addon retrieves the client ID and client secret from its configuration
3. It injects these credentials into the OAuth request
4. It forwards the request to the configured OAuth provider
5. It returns the provider's response (including access token) to the client

## Security Considerations

- Client credentials (ID and secret) never leave the server
- CORS headers are properly configured to allow secure cross-origin requests
- For production use, always use HTTPS
- The addon forwards cookies from the OAuth provider

## Troubleshooting

If you encounter issues:

1. Check that your client ID and secret are correctly entered in the addon settings
2. Verify that the provider URL is accessible from your server
3. Ensure your REDAXO installation has curl extension enabled
4. Review browser console for any CORS or network errors
5. Verify that your OAuth provider supports the client credentials grant type

## License

MIT License

## Author

oldjazjef - [GitHub](https://github.com/oldjazjef)

## Support

For issues and feature requests, please use the [GitHub repository](https://github.com/oldjazjef/redaxo-addon-oauth-proxy).

document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Initialize framework
    const mainScriptEl = document.querySelector('script[src*="js/main.js"]');
    const mainScriptUrl = mainScriptEl ? new URL(mainScriptEl.src, window.location.origin) : null;
    const assetVersion = mainScriptUrl ? mainScriptUrl.searchParams.get('v') : '';
    const withAssetVersion = (src) => {
      if (!assetVersion) {
        return src;
      }

      const url = new URL(src, window.location.origin);
      url.searchParams.set('v', assetVersion);

      return url.toString();
    };

    await Now.init({
      // Environment mode: 'development' or 'production'
      environment: 'production',

      // Path configuration for templates and resources
      paths: {
        components: `${WEB_URL}components`,
        plugins: `${WEB_URL}plugins`,
        templates: `${WEB_URL}templates`,
        translations: `${WEB_URL}language`
      },

      // Enable framework-level auth so AuthManager will initialize before RouterManager
      auth: {
        enabled: true,
        autoInit: true,
        endpoints: {
          verify: `${WEB_URL}api/index/auth/verify`, // Used to check if the Token/Cookie sent by Client (such as Authorization Header or Cookie) is still correct/not expired or not.
          me: `${WEB_URL}api/index/auth/me`, // Restore the current user (Profile)
          login: `${WEB_URL}api/index/auth/login`, // Get a Creitedial (Email/Password) and reply token/session + user info.
          logout: `${WEB_URL}api/index/auth/logout`, // Cancel session /invalidates token at Server
          refresh: `${WEB_URL}api/index/auth/refresh`  // Used to ask for a new Token when the TOKEN is currently expired (if using JWT or Token-based Author)
        },

        token: {
          storageKey: 'auth_user'
        },

        redirects: {
          afterLogin: ``,
          afterLogout: `login`,
          unauthorized: `login`,
          forbidden: `403`
        }

        // Optional: Add custom auth-related keys to remove on logout
        // Default keys (always removed): auth_user, auth_token, refresh_token,
        // auth_session, user_session, login_data
        //
        // security: {
        //   clearAuthKeysOnLogout: [
        //     'my_custom_session',    // Your custom auth key
        //     'oauth_state'           // OAuth state
        //   ]
        // }
      },

      // Security configuration (CSRF token endpoint configurable here)
      security: {
        csrf: {
          enabled: true,
          tokenName: '_token',
          headerName: 'X-CSRF-Token',
          cookieName: 'XSRF-TOKEN',
          metaName: 'csrf-token',
          tokenUrl: `${WEB_URL}api/index/auth/csrf-token` // CSRF endpoin
        }
      },

      // Internationalization settings
      i18n: {
        enabled: true,
        availableLocales: ['en', 'th'],
        // The server owns the site language (?lang= → my_lang cookie → <html lang>),
        // so don't let a stored choice — e.g. the admin's toggle — override it
        storageKey: null
      },

      // Application Configuration (Theme + Site Metadata)
      config: {
        enabled: true,
        defaultTheme: 'light',
        storageKey: 'bookstore_theme',
        systemPreference: false, // Not use system color scheme preference

        // Smooth transitions
        transition: {
          enabled: true,
          duration: 300,
          hideOnSwitch: true
        },

        // API config - auto-load theme + site metadata from server on init
        api: {
          enabled: true,
          configUrl: `${WEB_URL}api/index/config/frontend-settings`,  // Returns { variables, site }
          cacheResponse: true
        }
      },

      router: {
        enabled: false,
        mode: 'history', // 'hash' or 'history'

        // Auth Configuration for Router
        auth: {
          enabled: true,
          autoGuard: true,
          defaultRequireAuth: true,
          publicPaths: ['/login', '/404'],
          guestOnlyPaths: ['/login'],
          // Targets are relative to this app's base (WEB_URL) so they are correct
          // for both root and sub-directory deployments. RedirectManager is the
          // single authority that consumes these (after_login honors an intended
          // route first, then falls back to afterLogin = this app's home).
          redirects: {
            unauthenticated: `${WEB_URL}login`,
            unauthorized: `${WEB_URL}login`,
            forbidden: `${WEB_URL}403`,
            afterLogin: WEB_URL,
            afterLogout: `${WEB_URL}login`
          }
        },

        notFound: {
          behavior: 'render',
          template: '404.html',
          title: 'Page Not Found'
        }
      },

      scroll: {
        enabled: true,
        selectors: {
          content: '#main',
        }
      }
    }).then(() => {
      // Load application components after framework initialization
      const scripts = [
        withAssetVersion(`${WEB_URL}js/components/SocialLogin.js`),
        withAssetVersion(`${WEB_URL}js/components/Carousel.js`),
        withAssetVersion(`${WEB_URL}js/components/GallerySlideshow.js`),
        withAssetVersion(`${WEB_URL}js/components/CounterComponent.js`),
        withAssetVersion(`${WEB_URL}js/components/CookieBanner.js`)
      ];

      // Dynamically load all component scripts
      scripts.forEach(src => {
        const script = document.createElement('script');
        script.src = src;
        document.head.appendChild(script);
      });
    });

    // Create application instance
    const app = await Now.createApp({
      name: 'GCMS',
      version: '1.0.0'
    });

    document.dispatchEvent(new CustomEvent('gcms:now-ready', {bubbles: true}));

  } catch (error) {
    console.error('Application initialization failed:', error);
  }
});


function doLogout(e) {
  e.preventDefault();
  if (window.AuthManager) {
    AuthManager.logout().then(() => {
      window.location.reload();
    }).catch(err => {
      console.error('Logout failed:', err);
    });
  }
}

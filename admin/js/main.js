/**
 * Main Application - Admin Panel
 * Now.js Framework
 */

// Permission Helper Functions
const isAdmin = (user) => user?.status === 1;
const isSuperAdmin = (user) => user?.status === 1 && user?.id === 1;
const hasPermission = (user, permission) => {
  if (isAdmin(user)) return true; // Admin ทำได้ทุกอย่าง
  return user?.permission?.includes(permission);
};

// Route Guards
const requireAdmin = async (params, current, authManager) => {
  const user = authManager.getUser();
  if (!isAdmin(user)) return '/403';
  return true;
};

const requirePermission = (permission) => async (params, current, authManager) => {
  const user = authManager.getUser();
  if (!hasPermission(user, permission)) return '/403';
  return true;
};

document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Detect base directory from script src (safe for SPA history mode)
    // Using window.location.pathname would break when URL is a deep route like /admin/widgets/facebook
    const mainScriptEl = document.querySelector('script[src*="js/main.js"]');
    let currentDir;
    if (mainScriptEl) {
      const scriptUrl = new URL(mainScriptEl.src);
      currentDir = scriptUrl.pathname.replace(/\/js\/main\.js$/, '') + '/';
    } else {
      const currentPath = window.location.pathname;
      currentDir = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
    }

    // Parent directory of admin (project root), as a full origin-prefixed URL.
    // Must use http:// so TemplateManager skips Now.resolvePath() (which strips ../),
    // and fetches the file directly.
    window.WEB_URL = window.location.origin + currentDir.replace(/[^/]+\/$/, '');

    // Initialize framework
    await Now.init({
      // Environment mode: 'development' or 'production'
      environment: 'production',

      // Path configuration for templates and resources
      paths: {
        components: `${currentDir}components`,
        plugins: `${currentDir}plugins`,
        templates: `${currentDir}templates`,
        translations: `${currentDir}../language`
      },

      // Enable framework-level auth so AuthManager will initialize before RouterManager
      auth: {
        enabled: true,
        autoInit: true,
        endpoints: {
          verify: `${currentDir}../api/index/auth/verify`, // Used to check if the Token/Cookie sent by Client (such as Authorization Header or Cookie) is still correct/not expired or not.
          me: `${currentDir}../api/index/auth/me`, // Restore the current user (Profile)
          login: `${currentDir}../api/index/auth/login`, // Get a Creitedial (Email/Password) and reply token/session + user info.
          logout: `${currentDir}../api/index/auth/logout`, // Cancel session /invalidates token at Server
          refresh: `${currentDir}../api/index/auth/refresh`  // Used to ask for a new Token when the TOKEN is currently expired (if using JWT or Token-based Author)
        },

        token: {
          storageKey: 'auth_user'
        },

        redirects: {
          afterLogin: `${currentDir}../`,
          afterLogout: `${currentDir}../login`,
          unauthorized: `${currentDir}../login`,
          forbidden: `${currentDir}../403`
        }
      },

      // Security configuration (CSRF token endpoint configurable here)
      security: {
        csrf: {
          enabled: true,
          tokenName: '_token',
          headerName: 'X-CSRF-Token',
          cookieName: 'XSRF-TOKEN',
          metaName: 'csrf-token',
          tokenUrl: `${currentDir}../api/index/auth/csrf-token` // CSRF endpoin
        }
      },

      // Internationalization settings
      i18n: {
        enabled: true,
        availableLocales: ['en', 'th']
      },

      // AppConfigManager Configuration (Theme + Site Metadata)
      config: {
        enabled: true,
        defaultTheme: 'light',
        storageKey: 'crm_theme',
        systemPreference: false, // Not use system color scheme preference

        // Smooth transitions
        transition: {
          enabled: true,
          duration: 300,
          hideOnSwitch: true
        },

        // API config - auto-load from server on init
        api: {
          enabled: true,
          configUrl: `${currentDir}../api/index/config/frontend-settings?scope=admin`,  // Automatically loads admin theme variables during init
          cacheResponse: true,
          headers: {
            'X-Page-URL': window.location.href  // Send current SPA page URL to API for query string parsing
          }
        }
      },

      router: {
        enabled: true,
        base: currentDir,
        mode: 'history', // 'hash' or 'history'

        // Auth Configuration for Router
        auth: {
          enabled: true,
          autoGuard: true,
          defaultRequireAuth: true,
          publicPaths: ['/login', '/404'],
          guestOnlyPaths: ['/login'],
          // Targets are absolute paths within this admin app's base (currentDir,
          // e.g. /projects/admin/) so RedirectManager lands users on the admin
          // home/login regardless of deployment. after_login honors an intended
          // route first, then falls back to afterLogin = the admin home.
          redirects: {
            unauthenticated: `${currentDir}login`,
            unauthorized: `${currentDir}login`,
            forbidden: `${currentDir}403`,
            afterLogin: currentDir,
            afterLogout: `${currentDir}login`
          }
        },

        notFound: {
          behavior: 'render',
          template: '404.html',
          title: 'Page Not Found'
        },

        routes: {
          '/': {
            template: 'index.html',
            title: '{LNG_Dashboard}',
            requireGuest: false,
            requireAuth: true
          },
          '/login': {
            template: 'login.html',
            title: '{LNG_Login}',
            requireGuest: true,
            requireAuth: false
          },
          '/forgot': {
            template: 'forgot.html',
            title: '{LNG_Forgot Password}',
            requireGuest: true,
            requireAuth: false
          },
          '/register': {
            template: 'register.html',
            title: '{LNG_Register}',
            requireGuest: true,
            requireAuth: false
          },
          '/reset-password': {
            template: 'reset-password.html',
            title: '{LNG_Reset Password}',
            requireGuest: true,
            requireAuth: false
          },
          '/activate': {
            template: 'activate.html',
            title: '{LNG_Activate Account}',
            requireGuest: true,
            requireAuth: false
          },
          '/logout': {
            requireAuth: false,
            beforeEnter: async (params, current, authManager) => {
              await authManager.logout();
              return '/login';
            }
          },
          '/profile': {
            template: 'profile.html',
            title: '{LNG_Profile}',
            menuPath: '/users',
            requireAuth: true
          },
          '/users': {
            template: 'users.html',
            title: '{LNG_Users}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/user-status': {
            template: 'settings/userstatus.html',
            title: '{LNG_Member status}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/general-settings': {
            template: 'settings/general.html',
            title: '{LNG_General Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/company-settings': {
            template: 'settings/company.html',
            title: '{LNG_Company Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/meta': {
            template: 'settings/meta.html',
            title: '{LNG_SEO & Social}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/email-settings': {
            template: 'settings/email.html',
            title: '{LNG_Email Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/api-settings': {
            template: 'settings/api.html',
            title: '{LNG_API Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/database-backup': {
            template: 'settings/database-backup.html',
            title: 'Database Import/Export Management',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/theme-settings': {
            template: 'settings/theme.html',
            title: '{LNG_Theme Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/themes': {
            template: 'settings/themes.html',
            title: '{LNG_Choose Theme}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/line-settings': {
            template: 'settings/line.html',
            title: '{LNG_Line Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/telegram-settings': {
            template: 'settings/telegram.html',
            title: '{LNG_Telegram Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/sms-settings': {
            template: 'settings/sms.html',
            title: '{LNG_SMS Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/ai-settings': {
            template: 'settings/ai.html',
            title: '{LNG_AI Settings}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/ai-theme-generator': {
            template: 'settings/aitheme.html',
            title: '{LNG_AI Theme Generator}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/cookie-policy': {
            template: 'settings/cookie-policy.html',
            title: '{LNG_Cookie Policy}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/languages': {
            template: 'settings/languages.html',
            title: '{LNG_Manage languages}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/language': {
            template: 'settings/language.html',
            title: '{LNG_Add}/{LNG_Edit} {LNG_Language}',
            menuPath: '/languages',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/menus': {
            template: 'settings/menus.html',
            title: '{LNG_Manage menus}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/menu': {
            template: 'settings/menu.html',
            title: '{LNG_Add}/{LNG_Edit} {LNG_Menu}',
            menuPath: '/menus',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/pages': {
            template: 'settings/pages.html',
            title: '{LNG_Manage pages}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/page': {
            template: 'settings/page.html',
            title: '{LNG_Add}/{LNG_Edit} {LNG_Page}',
            menuPath: '/pages',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/modules': {
            template: 'settings/modules.html',
            title: '{LNG_Manage modules}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/module': {
            template: 'settings/module.html',
            title: '{LNG_Add}/{LNG_Edit} {LNG_Module}',
            menuPath: '/modules',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/intro': {
            template: 'settings/intro.html',
            title: '{LNG_Intro page}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/maintenance': {
            template: 'settings/maintenance.html',
            title: '{LNG_Maintenance mode}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/usage': {
            template: 'settings/usage.html',
            title: '{LNG_Usage history}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/mailtemplates': {
            template: 'settings/mailtemplates.html',
            title: '{LNG_Manage Mail templates}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/mailtemplate': {
            template: 'settings/mailtemplate.html',
            title: '{LNG_Add}/{LNG_Edit} {LNG_Mail Template}',
            menuPath: '/mailtemplates',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/widgets/:module': {
            template: `${WEB_URL}widgets/:module/:module.html`,
            title: '{LNG_Widget}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/widgets/:module/:page': {
            template: `${WEB_URL}widgets/:module/:page.html`,
            title: '{LNG_Widget}',
            requireAuth: true,
            beforeEnter: requireAdmin
          },
          '/403': {
            template: '403.html',
            title: '{LNG_Access Denied}',
            requireAuth: true
          },
          '/404': {
            template: '404.html',
            title: '{LNG_Page Not Found}'
          }
        }
      },

      scroll: {
        enabled: false,
        selectors: {
          content: '.content',
        }
      }
    }).then(() => {
      // Load application components after framework initialization
      const scripts = [
        `${currentDir}js/components/sidebar.js`,
        `${currentDir}js/components/topbar.js`,
        `${currentDir}../js/components/SocialLogin.js`,
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
      name: 'Now.js',
      version: '1.0.0'
    });

  } catch (error) {
    console.error('Application initialization failed:', error);
  }
});

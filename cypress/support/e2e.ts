// ***********************************************************
// This example support/e2e.ts is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands';
import '@testing-library/cypress/add-commands';

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Add custom before hook to handle Laravel CSRF tokens
beforeEach(() => {
  // Visit the page first to get CSRF token
  cy.visit('/');

  // Intercept CSRF token requests if needed
  cy.intercept('POST', '/api/**', (req) => {
    // Laravel Sanctum CSRF cookie handling
    cy.getCookie('XSRF-TOKEN').then((cookie) => {
      if (cookie) {
        req.headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie.value);
      }
    });
  });
});


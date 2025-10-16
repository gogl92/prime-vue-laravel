/// <reference types="cypress" />

// ***********************************************
// This example commands.ts shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Cypress {
    interface Chainable {
      /**
       * Custom command to login a user
       * @example cy.login('user@example.com', 'password')
       */
      login(email: string, password: string): Chainable<void>;

      /**
       * Custom command to logout
       * @example cy.logout()
       */
      logout(): Chainable<void>;

      /**
       * Custom command to seed the database
       * @example cy.seedDatabase()
       */
      seedDatabase(): Chainable<void>;

      /**
       * Custom command to find by test id
       * @example cy.getByTestId('submit-button')
       */
      getByTestId(testId: string): Chainable<JQuery<HTMLElement>>;
    }
  }
}

// Custom command to login
Cypress.Commands.add('login', (email: string, password: string) => {
  cy.visit('/login');
  cy.get('input[name="email"]').type(email);
  cy.get('input[name="password"]').type(password);
  cy.get('button[type="submit"]').click();
  cy.url().should('not.include', '/login');
});

// Custom command to logout
Cypress.Commands.add('logout', () => {
  cy.visit('/');
  // Adjust selector based on your logout button
  cy.contains('Logout').click();
  cy.url().should('include', '/login');
});

// Custom command to seed database via API
Cypress.Commands.add('seedDatabase', () => {
  cy.exec('php artisan migrate:fresh --seed', { timeout: 30000 });
});

// Custom command to get element by test id
Cypress.Commands.add('getByTestId', (testId: string) => {
  return cy.get(`[data-testid="${testId}"]`);
});

export {};


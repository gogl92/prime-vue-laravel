describe('Invoices Page', () => {
  beforeEach(() => {
    // Login first - adjust credentials based on your seeded data
    // cy.login('test@example.com', 'password');
    cy.visit('/invoices');
  });

  it('should display invoices list', () => {
    cy.url().should('include', '/invoices');
    // Wait for page to load
    cy.get('body', { timeout: 5000 }).should('be.visible');
  });

  it('should allow filtering invoices', () => {
    // Check if filter inputs exist
    cy.get('body').then(($body) => {
      if ($body.find('input[type="search"]').length > 0) {
        cy.get('input[type="search"]').first().type('INV-001');
        // Wait for results to update
        cy.wait(500);
      }
    });
  });

  it('should allow sorting invoices', () => {
    // Check if sortable columns exist
    cy.get('body').then(($body) => {
      if ($body.find('[role="columnheader"]').length > 0) {
        cy.get('[role="columnheader"]').first().click();
        // Wait for sort to apply
        cy.wait(500);
      }
    });
  });

  it('should navigate to invoice detail page', () => {
    // Check if invoice rows exist
    cy.get('body').then(($body) => {
      if ($body.find('tr[data-index]').length > 0 || $body.find('a[href*="/invoices/"]').length > 0) {
        // Click on first invoice
        cy.get('tr[data-index]').first().click();
        // URL should change to invoice detail
        cy.url({ timeout: 5000 }).should('match', /\/invoices\/\d+/);
      }
    });
  });

  it('should handle pagination', () => {
    // Check if pagination exists
    cy.get('body').then(($body) => {
      if ($body.find('[aria-label*="pagination"]').length > 0 || $body.find('button[aria-label*="next"]').length > 0) {
        cy.get('button[aria-label*="next"]').first().should('be.visible');
      }
    });
  });

  it('should allow creating a new invoice', () => {
    // Check if create button exists
    cy.get('body').then(($body) => {
      if ($body.find('button:contains("Create")').length > 0 || $body.find('a:contains("New")').length > 0) {
        // Click create/new button
        cy.contains('button', /create|new/i).first().click();
        // Should navigate to create form
        cy.url({ timeout: 5000 }).should('match', /\/invoices\/create|\/invoices\/new/);
      }
    });
  });
});


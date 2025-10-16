describe('Example E2E Test', () => {
  beforeEach(() => {
    cy.visit('/');
  });

  it('should load the homepage', () => {
    cy.url().should('include', '/');
    cy.get('body').should('be.visible');
  });

  it('should have proper meta tags', () => {
    cy.get('head title').should('exist');
  });

  it('should be responsive', () => {
    // Test desktop view
    cy.viewport(1280, 720);
    cy.get('body').should('be.visible');

    // Test tablet view
    cy.viewport('ipad-2');
    cy.get('body').should('be.visible');

    // Test mobile view
    cy.viewport('iphone-x');
    cy.get('body').should('be.visible');
  });

  it('should handle navigation', () => {
    // This is an example - adjust selectors based on your actual navigation
    cy.get('nav').should('exist');
  });
});


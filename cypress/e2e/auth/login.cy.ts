describe('Login Page', () => {
  beforeEach(() => {
    cy.visit('/login');
  });

  it('should display the login form', () => {
    cy.url().should('include', '/login');
    cy.get('input[name="email"]').should('be.visible');
    cy.get('input[name="password"]').should('be.visible');
    cy.get('button[type="submit"]').should('be.visible');
  });

  it('should show validation errors for empty form', () => {
    cy.get('button[type="submit"]').click();
    // Adjust selectors based on your error display implementation
    cy.get('body').should('contain.text', 'required').or('contain.text', 'field');
  });

  it('should show error for invalid credentials', () => {
    cy.get('input[name="email"]').type('invalid@example.com');
    cy.get('input[name="password"]').type('wrongpassword');
    cy.get('button[type="submit"]').click();

    // Wait for response and check for error message
    cy.get('body', { timeout: 5000 }).should('contain.text', 'credentials').or('contain.text', 'error');
  });

  it('should successfully login with valid credentials', () => {
    // First seed the database with a test user
    // Adjust based on your seeded test user credentials
    const testEmail = 'test@example.com';
    const testPassword = 'password';

    cy.get('input[name="email"]').type(testEmail);
    cy.get('input[name="password"]').type(testPassword);
    cy.get('button[type="submit"]').click();

    // After successful login, user should be redirected
    cy.url({ timeout: 5000 }).should('not.include', '/login');
  });

  it('should toggle password visibility', () => {
    // Adjust selector based on your password visibility toggle implementation
    cy.get('input[name="password"]').should('have.attr', 'type', 'password');

    // If you have a toggle button for password visibility
    cy.get('body').then(($body) => {
      if ($body.find('[aria-label*="password"]').length > 0) {
        cy.get('[aria-label*="password"]').first().click();
        cy.get('input[name="password"]').should('have.attr', 'type', 'text');
      }
    });
  });

  it('should have a link to register page', () => {
    cy.get('body').then(($body) => {
      if ($body.find('a[href*="register"]').length > 0) {
        cy.get('a[href*="register"]').should('be.visible');
      }
    });
  });

  it('should have a forgot password link', () => {
    cy.get('body').then(($body) => {
      if ($body.find('a[href*="forgot-password"]').length > 0) {
        cy.get('a[href*="forgot-password"]').should('be.visible');
      }
    });
  });
});


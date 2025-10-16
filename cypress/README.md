# Cypress Testing Documentation

This project uses [Cypress](https://www.cypress.io/) for end-to-end (E2E) and component testing.

## Installation

Cypress and its dependencies are already installed. If you need to reinstall:

```bash
npm install --save-dev cypress @cypress/vue @testing-library/cypress
```

## Running Tests

### Interactive Mode (Cypress Test Runner)

Open Cypress Test Runner to run tests interactively and see them execute in a browser:

```bash
# Open Cypress (choose between E2E or Component tests)
npm run cypress:open

# Open E2E tests specifically
npm run cypress:open:e2e

# Open Component tests specifically
npm run cypress:open:component
```

### Headless Mode (CI/CD)

Run tests in headless mode for continuous integration:

```bash
# Run all tests
npm test

# Run E2E tests only
npm run test:e2e
# or
npm run cypress:run:e2e

# Run Component tests only
npm run test:component
# or
npm run cypress:run:component
```

## Project Structure

```
cypress/
├── e2e/                    # End-to-end test specs
│   ├── auth/              # Authentication-related tests
│   │   └── login.cy.ts
│   ├── example.cy.ts
│   └── invoices.cy.ts
├── component/             # Component test specs
│   └── example.cy.ts
├── fixtures/              # Test data fixtures
│   └── example.json
├── support/               # Support files and custom commands
│   ├── commands.ts        # Custom Cypress commands
│   ├── component.ts       # Component testing setup
│   └── e2e.ts            # E2E testing setup
└── tsconfig.json         # TypeScript configuration for Cypress
```

## Configuration

The main configuration is in `cypress.config.ts` at the project root:

- **Base URL**: `http://localhost:8000` (adjust if your Laravel app runs on a different port)
- **Viewport**: 1280x720 (default)
- **Video Recording**: Disabled by default (enable in CI/CD if needed)
- **Screenshots**: Enabled on test failure

## Custom Commands

### Authentication Commands

```typescript
// Login with email and password
cy.login('user@example.com', 'password');

// Logout
cy.logout();
```

### Database Commands

```typescript
// Seed the database
cy.seedDatabase();
```

### Testing Library Commands

```typescript
// Get element by test ID
cy.getByTestId('submit-button');

// Testing Library commands (from @testing-library/cypress)
cy.findByRole('button', { name: /submit/i });
cy.findByText('Welcome');
cy.findByLabelText('Email');
```

## Writing Tests

### E2E Test Example

```typescript
describe('Feature Name', () => {
  beforeEach(() => {
    cy.visit('/your-page');
  });

  it('should perform an action', () => {
    cy.get('[data-testid="button"]').click();
    cy.url().should('include', '/expected-path');
    cy.contains('Expected Text').should('be.visible');
  });
});
```

### Component Test Example

```typescript
import MyComponent from '@/components/MyComponent.vue';

describe('MyComponent', () => {
  it('should render correctly', () => {
    cy.mount(MyComponent, {
      props: {
        title: 'Test Title',
      },
    });
    
    cy.contains('Test Title').should('be.visible');
  });
});
```

## Best Practices

1. **Use data-testid attributes** for selecting elements in production code:
   ```html
   <button data-testid="submit-button">Submit</button>
   ```

2. **Use beforeEach for setup** to ensure test isolation:
   ```typescript
   beforeEach(() => {
     cy.visit('/page');
   });
   ```

3. **Use fixtures for test data**:
   ```typescript
   cy.fixture('example.json').then((data) => {
     // Use data in your test
   });
   ```

4. **Avoid hard-coded waits** - use Cypress's built-in retry-ability:
   ```typescript
   // Bad
   cy.wait(1000);
   
   // Good
   cy.get('[data-testid="element"]', { timeout: 5000 }).should('be.visible');
   ```

5. **Use custom commands** for repeated actions to keep tests DRY.

6. **Test user flows, not implementation details**.

## Integration with Laravel

### Running Laravel for Tests

Before running Cypress tests, ensure your Laravel application is running:

```bash
# Start Laravel development server
php artisan serve

# Or use Sail if you're using Docker
./vendor/bin/sail up
```

### Database Seeding

For consistent test data, you can seed your database before tests:

```bash
php artisan migrate:fresh --seed
```

Or use the custom command in your tests:

```typescript
cy.seedDatabase();
```

## Debugging

### Visual Debugging

When running in interactive mode (`cypress:open`), you can:
- Use the Cypress Test Runner's time-travel debugging
- See each command as it executes
- View snapshots of the DOM at each step
- Access browser DevTools

### Console Logs

Add debug logs in your tests:

```typescript
cy.get('[data-testid="element"]').then(($el) => {
  console.log('Element:', $el);
});
```

### Screenshots and Videos

Screenshots are automatically taken on test failure. Enable video recording in `cypress.config.ts` if needed:

```typescript
{
  video: true,
}
```

## CI/CD Integration

Example GitHub Actions workflow:

```yaml
- name: Run Cypress tests
  run: |
    npm run build
    php artisan serve &
    npm run test:e2e
```

## Additional Resources

- [Cypress Documentation](https://docs.cypress.io/)
- [Cypress Best Practices](https://docs.cypress.io/guides/references/best-practices)
- [Testing Library Documentation](https://testing-library.com/docs/cypress-testing-library/intro/)
- [Vue Test Utils](https://test-utils.vuejs.org/)

## Troubleshooting

### Port Issues

If your Laravel app runs on a different port, update `baseUrl` in `cypress.config.ts`.

### CSRF Token Issues

The E2E support file includes CSRF token handling for Laravel Sanctum. If you encounter authentication issues, check the `cypress/support/e2e.ts` file.

### TypeScript Errors

Ensure all Cypress types are properly installed and the `cypress/tsconfig.json` includes the necessary type definitions.


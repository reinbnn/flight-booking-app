# Complete Testing Checklist

## Frontend Testing

### Homepage (index.html)
- [ ] Page loads without errors
- [ ] Search form is functional
- [ ] Navigation menu works
- [ ] Responsive on mobile
- [ ] All links work

### Authentication Pages
- [ ] Login page (`pages/login.html`)
  - [ ] Form validation
  - [ ] Submit button works
  - [ ] Error messages display
  
- [ ] Register page (`pages/register.html`)
  - [ ] Form validation
  - [ ] Password strength indicator
  - [ ] Email validation
  
- [ ] Password reset flow
  - [ ] Request form works
  - [ ] Email verification
  - [ ] Password update

### Booking Pages
- [ ] Flight search results (`pages/flights-results.html`)
  - [ ] Display flights correctly
  - [ ] Filtering works
  - [ ] Sorting works
  - [ ] Book button triggers payment
  
- [ ] Booking confirmation (`pages/booking-confirmation.html`)
  - [ ] Shows correct details
  - [ ] Download invoice button
  - [ ] Print button works
  
- [ ] My Bookings (`pages/my-bookings.html`)
  - [ ] Lists user bookings
  - [ ] Cancel booking works
  - [ ] Refund request works

### Payment Pages
- [ ] Payment page (`pages/payment.html`)
  - [ ] Form validation
  - [ ] Card validation
  - [ ] Submit works
  
- [ ] Payment success (`pages/payment-success.html`)
  - [ ] Shows confirmation
  - [ ] Download invoice option
  
- [ ] Payment methods (`pages/payment-methods.html`)
  - [ ] Add payment method
  - [ ] Delete payment method
  - [ ] Set as default

### Profile Pages
- [ ] Profile page (`pages/profile.html`)
  - [ ] Display user info
  - [ ] Edit profile
  - [ ] Change password
  - [ ] 2FA setup

## Backend API Testing

### Flights API
- [ ] GET /api/flights.php - List flights
- [ ] GET /api/flights.php?id=123 - Get single flight
- [ ] GET /api/search.php - Search flights

### Booking API
- [ ] GET /api/bookings.php - List user bookings
- [ ] POST /api/bookings.php - Create booking
- [ ] GET /api/bookings.php?id=123 - Get booking details

### Payment API
- [ ] POST /api/payments.php - Create payment
- [ ] POST /api/create-payment-intent.php - Stripe integration
- [ ] POST /api/paypal-create-order.php - PayPal integration
- [ ] GET /api/payment-details.php?id=123 - Get payment details
- [ ] POST /api/confirm-payment.php - Confirm payment

### User API
- [ ] POST /api/login.php - User login
- [ ] POST /api/users.php - Create user (registration)
- [ ] GET /api/users.php - Get user info
- [ ] POST /api/change-password.php - Change password
- [ ] POST /api/reset-password.php - Reset password

### Refund API
- [ ] POST /api/request-refund.php - Request refund
- [ ] GET /api/refund-status.php?id=123 - Check refund status
- [ ] POST /api/approve-refund.php - Admin approve refund
- [ ] POST /api/reject-refund.php - Admin reject refund
- [ ] POST /api/process-refund.php - Process refund

### Email API
- [ ] POST /api/send-confirmation.php - Send booking confirmation
- [ ] POST /api/send-receipt.php - Send payment receipt
- [ ] POST /api/send-cancellation.php - Send cancellation email
- [ ] POST /api/send-payment-failed.php - Send payment failed email
- [ ] POST /api/send-reminder.php - Send booking reminder

### Admin API
- [ ] GET /api/admin/ - List admin endpoints
- [ ] POST /api/admin/[endpoint] - Various admin functions

## Database Testing

- [ ] Database connection works
- [ ] Tables created successfully
- [ ] Migrations run without errors
- [ ] Data persists correctly
- [ ] Queries execute properly

## Integration Testing

- [ ] Complete booking flow (search → book → pay → confirm)
- [ ] Complete refund flow (request → approve → process)
- [ ] Email notifications send correctly
- [ ] Payment confirmations received
- [ ] Admin notifications received

## Security Testing

- [ ] SQL injection protection
- [ ] XSS prevention
- [ ] CSRF token validation
- [ ] Password hashing
- [ ] Session security
- [ ] API authentication
- [ ] Rate limiting
- [ ] Input validation

## Performance Testing

- [ ] API response time < 1 second
- [ ] Page load time < 3 seconds
- [ ] Database queries optimized
- [ ] No memory leaks
- [ ] Concurrent user handling


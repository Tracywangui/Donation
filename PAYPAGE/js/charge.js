// Create a Stripe client with your Publishable Key
const stripe = Stripe('pk_test_51QLPQnA7eKA98adrx9yynoNnr7ZMkdtObCGtAqx1FHt73n5HK4sEtY3sanAZJ4evE7fKc9QOYlER8DBgc8RlL7mP00dgtfSVdS');

// Create an instance of Elements
const elements = stripe.elements();

// Custom styling
const style = {
  base: {
    color: '#32325d',
    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
    fontSmoothing: 'antialiased',
    fontSize: '16px',
    '::placeholder': {
      color: '#aab7c4'
    }
  },
  invalid: {
    color: '#fa755a',
    iconColor: '#fa755a'
  }
};

// Create an instance of the card Element
const card = elements.create('card', { 
    style: style,
    hidePostalCode: true // Add this if you don't want to collect postal code
});

// Add an instance of the card Element into the `card-element` <div>
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.addEventListener('change', function(event) {
  const displayError = document.getElementById('card-errors');
  if (event.error) {
    displayError.textContent = event.error.message;
  } else {
    displayError.textContent = '';
  }
});

// Handle form submission
const form = document.getElementById('payment-form');
form.addEventListener('submit', function(event) {
  event.preventDefault();

  // Disable the submit button to prevent repeated clicks
  document.querySelector('#payment-form button').disabled = true;

  stripe.createToken(card).then(function(result) {
    if (result.error) {
      // Inform the user if there was an error
      const errorElement = document.getElementById('card-errors');
      errorElement.textContent = result.error.message;
      // Re-enable the submit button
      document.querySelector('#payment-form button').disabled = false;
    } else {
      // Send the token to your server
      stripeTokenHandler(result.token);
    }
  });
});

function stripeTokenHandler(token) {
  // Insert the token ID into the form so it gets submitted to the server
  const form = document.getElementById('payment-form');
  const hiddenInput = document.createElement('input');
  hiddenInput.setAttribute('type', 'hidden');
  hiddenInput.setAttribute('name', 'stripeToken');
  hiddenInput.setAttribute('value', token.id);
  form.appendChild(hiddenInput);

  // Submit the form
  form.submit();
}

// Style button with BS (moved to the end)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#payment-form button').classList = 'btn btn-primary btn-block mt-4';
});
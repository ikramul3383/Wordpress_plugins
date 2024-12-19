jQuery(document).ready(function ($) {
  console.log("Donation handling script is loaded");

  // Update the display amount field whenever a radio button is selected
  $('#donation-radio-buttons input[type="radio"]').on('change', function () {
    var amount = $(this).val(); // Get the value of the selected radio button
    $('#display-amount').val(amount); // Update the display amount field
    console.log('Radio selected: ' + amount); // Log for debugging
  });

  // Update the display amount field whenever a value is entered in the custom donation text field
  $('input[name="custom_donation"]').on('input', function () {
    var amount = $(this).val(); // Get the value entered in the text field
    $('#display-amount').val(amount); // Update the display amount field
    console.log('Custom input: ' + amount); // Log for debugging
  });

  // Handle the "Donate Now" button click
  $('#donate-now').on('click', function (e) {
    e.preventDefault(); // Prevent the default form submit or action

    var amount = $('#display-amount').val(); // Get the current value in the display amount field

    // Log the donation amount on button click
    console.log('Donate Now clicked. Donation amount: ' + amount);

    // Ensure the value is a valid number
    amount = parseFloat(amount);

    // Check if the amount is valid
    if (!isNaN(amount) && amount > 0) {
      console.log('Valid donation amount entered: ' + amount);
      $(this).prop('disabled', true); // Disable the button to prevent multiple clicks
      sendDonationAmountToServer(amount); // Send the valid donation amount to the server
    } else {
      console.log('Invalid donation amount entered.');
      alert('Please enter a valid donation amount.');
    }
  });

  // Function to send the donation amount to the server via AJAX
  function sendDonationAmountToServer(amount) {
    console.log('Sending donation amount to server: ' + amount);

    if (amount) {
      $.ajax({
        url: woocommerce_params.ajax_url, // WooCommerce AJAX URL
        type: 'POST',
        data: {
          action: 'add_donation_fee', // Custom action for donation
          donation_amount: amount // The donation amount
        },
        success: function (response) {
          console.log('AJAX success response:', response);
          if (response.success) {
            console.log('Donation added successfully');
            // Update the cart totals on the frontend
            $('.cart-total .woocommerce-Price-amount').text(response.data.new_total);
            console.log('Frontend cart total updated to: ' + response.data.new_total);

            // Trigger a WooCommerce cart refresh
            $(document.body).trigger('updated_wc_div');
            showDonationConfirmation();
          } else {
            console.log('Error: ' + response.data.message);
            alert('Donation could not be added: ' + response.data.message); // Show user-friendly message
          }
        },
        error: function (error) {
          console.log('AJAX error:', error);
          alert('There was an error processing your donation. Please try again.');
        }
      });
    } else {
      console.log('Donation amount is invalid or not provided.');
    }
  }

  // Function to show a confirmation message
  function showDonationConfirmation() {
    // Log to verify the function is being triggered
    console.log("Showing donation confirmation message...");

    // Create a confirmation message
    var message = $('<div class="donation-success-message"></div>')
      .text('Thank you for your donation! Your contribution has been added to your cart.');

    // Append the message to the body
    $('body').append(message);

    // Optionally, you can style it with CSS and make it disappear after a few seconds
    message.css({
      'position': 'fixed',
      'top': '20px',
      'left': '50%',
      'transform': 'translateX(-50%)',
      'background-color': '#4CAF50',
      'color': 'white',
      'padding': '10px 20px',
      'font-size': '16px',
      'border-radius': '5px',
      'box-shadow': '0 4px 6px rgba(0, 0, 0, 0.1)',
      'z-index': '9999',
      'opacity': '1',
      'display': 'block'
    });

    // Fade the message out after 5 seconds
    setTimeout(function () {
      console.log("Fading out the message...");
      message.fadeOut(500, function () {
        $(this).remove();
      });
    }, 5000); // Message disappears after 5 seconds
  }
});


jQuery(document).ready(function ($) {
  // When a radio button is selected, hide the custom donation text field
  $('#donation-radio-buttons input').on('change', function () {
    $('#donation-text-field').hide();
    // Clear the custom donation input field when a radio button is selected
    $('input[name="custom_donation"]').val('');
  });

  // When the user starts typing in the custom donation text field, unselect the radio buttons
  $('#donation-text-field input').on('input', function () {
    $('#donation-radio-buttons input').prop('checked', false);
  });

  // Initially, if no radio button is selected, show the custom donation field
  if ($('#donation-radio-buttons input:checked').length === 0) {
    $('#donation-text-field').show();
  } else {
    $('#donation-text-field').hide(); // Hide the custom field if a radio button is selected
  }

  // Button to switch to custom donation amount
  $('#custom-donation-btn').on('click', function (e) {
    e.preventDefault();
    // Uncheck all radio buttons and show the custom donation field
    $('#donation-radio-buttons input').prop('checked', false);
    $('#donation-text-field').show();
  });

  // Append the donation information to the cart before checkout
  $('form.cart').submit(function () {
    var donationAmount = $('input[name="donation_amount"]:checked').val() || $('input[name="custom_donation"]').val();

    if (donationAmount) {
      var donationInput = $('<input>', {
        type: 'hidden',
        name: 'donation_amount',
        value: donationAmount
      });
      $(this).append(donationInput);
    }
  });
});
jQuery(document).ready(function ($) {
  $('form.cart').on('submit', function (e) {
    e.preventDefault();

    var donationAmount = $('input[name="donation_amount"]:checked').val();
    var customDonation = $('input[name="custom_donation"]').val();

    // Attach donation amount to the form data
    var formData = $(this).serialize() + '&donation_amount=' + donationAmount + '&custom_donation=' + customDonation;

    $.ajax({
      url: wc_add_to_cart_params.ajax_url,
      type: 'POST',
      data: formData,
      success: function (response) {
        // Handle success response (e.g., update cart, redirect to cart page)
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
      }
    });
  });
});

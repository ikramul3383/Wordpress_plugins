// Add more donation amount fields dynamically
document.getElementById('add_donation_amount').addEventListener('click', function() {
    var newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.name = 'donation_amounts[]';
    newInput.classList.add('regular-text');
    
    // Append the new input field and a line break
    var donationContainer = document.getElementById('donation_amounts');
    donationContainer.appendChild(newInput);
    donationContainer.appendChild(document.createElement('br'));
});

// Display acknowledgment message when settings are saved
document.addEventListener('DOMContentLoaded', function() {
    var message = document.getElementById('acknowledgment-message');
    if (message) {
        message.style.display = 'block';
        setTimeout(function() {
            message.style.display = 'none';
        }, 3000);
    }
});

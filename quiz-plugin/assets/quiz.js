jQuery(document).ready(function ($) {
    // Initialize variables
    let questions = [];
    let currentQuestionIndex = 0;
    let userAnswers = {};
    let userData = {};

    // Show loader function
    function showLoader() {
        $('#quiz-popup').append('<div class="loader">Loading...</div>');
    }

    // Hide loader function
    function hideLoader() {
        $('.loader').remove();
    }

    if (sessionStorage.getItem('quizSubmitted') === 'true') {
        $('#start-quiz').hide();
    }

    // Show registration form first
    function showRegistrationForm() {
        $('#quiz-popup').html(`
            <h3>User Registration</h3>
            <form id="registration-form">
              <input type="hidden" name="quiz_nonce" value="${quiz_ajax.quiz_nonce}">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required><br><br>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required><br><br>
                <label for="contact">Contact Number:</label>
                <input type="text" name="contact" id="contact" required><br><br>
                <label for="customer_id">Customer ID (Unique):</label>
                <input type="text" name="customer_id" id="customer_id" required><br><br>
                <button type="submit" id="submit-registration">Submit</button>
            </form>
        `);
    }

    // Show quiz after registration
    function showQuiz() {
        showLoader();
        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: { action: 'fetch_quiz_questions' },
            success: function (response) {
                questions = response;
                currentQuestionIndex = 0;
                displayQuestion();
                hideLoader();
            },
            error: function () {
                alert('Error loading quiz questions.');
                hideLoader();
            }
        });
    }

    // Submit registration form via AJAX
    $('#quiz-popup').on('submit', '#registration-form', function (e) {
        e.preventDefault();

        // Collect form data
        userData.username = $('#username').val();
        userData.email = $('#email').val();
        userData.contact = $('#contact').val();
        userData.customer_id = $('#customer_id').val();

        showLoader();
        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'register_user_for_quiz',
                user_data: userData,
                quiz_nonce: quiz_ajax.quiz_nonce
            },
            success: function (response) {
                if (response.success) {
                    showQuiz();
                } else {
                    alert(response.data.message);
                }
                hideLoader();
            },
            error: function () {
                alert(response.data.message);
                hideLoader();
            }
        });
    });

    // Display current question
    function displayQuestion() {
        let question = questions[currentQuestionIndex];
        $('#quiz-popup').html(`<h3>${question.question}</h3>`);
        question.answers.forEach((answer, index) => {
            $('#quiz-popup').append(`
                <label>
                    <input type="radio" name="answer" value="answer_${index + 1}">
                    ${answer}
                </label><br>
            `);
        });

        if (currentQuestionIndex === questions.length - 1) {
            $('#quiz-popup').append('<button id="submit-quiz">Submit Quiz</button>');
        } else {
            $('#quiz-popup').append('<button id="next-question">Next Question</button>');
        }
    }

    // Next Question Button Click
    $('#quiz-popup').on('click', '#next-question', function () {
        let selectedAnswer = $('input[name="answer"]:checked').val();
        if (selectedAnswer) {
            userAnswers[questions[currentQuestionIndex].ID] = selectedAnswer;
            currentQuestionIndex++;
            displayQuestion();
        } else {
            alert('Please select an answer');
        }
    });

    // quiz ertication code
    $('#quiz-popup').on('click', '#submit-quiz', function () {
        showLoader();

        let selectedAnswer = $('input[name="answer"]:checked').val();
        userAnswers[questions[currentQuestionIndex].ID] = selectedAnswer;
        let customer_id = userData.customer_id;
        let username = userData.username;
        let usercontact = userData.contact;
        console.log(username);
        // Debugging logs
        console.log('Selected Answer:', selectedAnswer);
        console.log('User Answers:', userAnswers);
        console.log('Customer ID:', customer_id);

        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_quiz',
                answers: userAnswers,
                user_id: customer_id,
                quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
            },
            success: function (response) {
                console.log(response); // For debugging
                if (response.success && response.data) {
                    let result = response.data;
                    let attempted = result.attempted || 0;
                    let correct = result.correct || 0;
                    let score = result.score || 0;

                    $('#quiz-popup').html(`
                    <h3>Quiz Result</h3>
                    <p>You attempted ${attempted} questions.</p>
                    <p>Correct answers: ${correct}</p>
                    <p>Score: ${score}%</p>
                `);

                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_coupon_code',
                            score: score,
                            username: username,
                            usercontact: usercontact,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (couponResponse) {
                            if (couponResponse.success) {
                                let couponCode = couponResponse.data.coupon_code;
                                let discount = couponResponse.data.discount;
                                $('#quiz-popup').append(`
                                <p>Congratulations! Your coupon code is: <strong>${couponCode}</strong></p>
                                 <p>You got a <strong>${discount}%</strong> discount!</p>
                            `);
                            } else {
                                $('#quiz-popup').append('<p>No coupon code available for this score.</p>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.log('Error fetching coupon code:', error);
                        }
                    });



                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_certificate',
                            score: score,
                            user_name: username,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (certificateResponse) {
                            if (certificateResponse.success) {
                                let certificateUrl = certificateResponse.data.certificate_url;
                                $('#quiz-popup').append(`
                                    <p>Your certificate is ready!</p>
                                    <a href="${certificateUrl}" target="_blank" download>
                                        <button class="download-certificate">Download Certificate</button>
                                    </a>
                                `);
                            } else {
                                $('#quiz-popup').append('<p>Certificate generation failed.</p>');
                            }
                            setTimeout(() => {
                                $('#quiz-popup').hide();
                            }, 5000);
                            $(this).hide();
                            sessionStorage.setItem('quizSubmitted', 'true');
                        },
                        error: function (xhr, status, error) {
                            console.log('Error generating certificate:', error);
                        }
                    });

                } else {
                    $('#quiz-popup').html('<p>Error: Unable to fetch quiz results.</p>');
                }
                hideLoader();
            },
            error: function (xhr, status, error) {
                console.log('AJAX error:', error);
                console.log('Response:', xhr.responseText);
                alert('Error submitting quiz.');
                hideLoader();
            }
        });
    });




    $(document).on('click', '#close-popup', function () {
        $('#quiz-popup').fadeOut();  // Close the popup by fading out
        $('body').removeClass('overlay');  // Remove the overlay class if you have one
    });

    // Open registration form when quiz starts
    $('#start-quiz').on('click', function () {
        $('#quiz-popup').fadeIn();
        showRegistrationForm();
    });

    // Close popup when clicked outside
    $(document).mouseup(function (e) {
        let popup = $("#quiz-popup");
        if (!popup.is(e.target) && popup.has(e.target).length === 0) {
            popup.fadeOut();
        }
    });
});


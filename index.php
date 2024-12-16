<?php
// Only process API requests when receiving POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    function getSalesforceToken() {
        $loginURL = 'https://tricore.my.salesforce.com/services/oauth2/token';
        $clientID = '3MVG9iTxZANhwHQsKolp1PnxAWx5F1BAsf0IrY67dOzoissl8JMltAp9UT0qomVLf6M42TA_vmuJyUZgLS51C';
        $clientSecret = 'F60FD137FD00B316F2F09217095194ACB723F003308881FEBDBF225E0D875239';
        
        $params = array(
            'grant_type' => 'client_credentials',
            'client_id' => $clientID,
            'client_secret' => $clientSecret
        );

        $ch = curl_init($loginURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return array('error' => 'Failed to get access token: ' . $error);
        }

        return json_decode($response, true);
    }

    function getButtonVisibility($accessToken) {
        $settingsURL = 'https://tricore.my.salesforce.com/services/apexrest/buttonVisibility';
        $ch = curl_init($settingsURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return array('error' => 'Failed to get button visibility: ' . $error);
        }

        return json_decode($response, true);
    }

    // Process API request
    $tokenResponse = getSalesforceToken();
    if (isset($tokenResponse['access_token'])) {
        $visibilityResponse = getButtonVisibility($tokenResponse['access_token']);
        echo json_encode($visibilityResponse);
    } else {
        echo json_encode(array('error' => 'Failed to obtain access token', 'details' => $tokenResponse));
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat Interface</title>
    <style>
        #chat-button-container {
            padding: 20px;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
        .error-message {
            color: red;
            margin: 10px 0;
        }
        .loading {
            display: none;
            color: #666;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div id="chat-button-container">
        <p>Chat Options</p>
        <button id="legacyChat" style="display: none;">Legacy Chat</button>
        <button id="miawChat" style="display: none;">MIAW Chat</button>
        <div id="loading" class="loading">Loading chat options...</div>
        <div id="error-container" class="error-message"></div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const chatButtonContainer = document.getElementById('chat-button-container');
            const legacyChatButton = document.getElementById('legacyChat');
            const miawChatButton = document.getElementById('miawChat');
            const errorContainer = document.getElementById('error-container');
            const loadingElement = document.getElementById('loading');

            // Fetch button visibility settings
            function fetchButtonVisibility() {
                loadingElement.style.display = 'block';
                errorContainer.textContent = '';

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loadingElement.style.display = 'none';
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    const showMIAWChat = data.showMIAWChat || false;
                    console.log('showMIAWChat value from Salesforce:', showMIAWChat);

                    // Update button visibility
                    if (showMIAWChat) {
                        miawChatButton.style.display = 'inline-block';
                        legacyChatButton.style.display = 'none';
                        console.log('MIAW Chat button visible');
                    } else {
                        legacyChatButton.style.display = 'inline-block';
                        miawChatButton.style.display = 'none';
                        console.log('Legacy Chat button visible');
                    }
                })
                .catch(error => {
                    loadingElement.style.display = 'none';
                    console.error('Error:', error);
                    errorContainer.textContent = 'Error loading chat options. Please try again later.';
                });
            }

            // Initialize LiveAgent
            function initializeLiveAgent() {
                console.log('Initializing LiveAgent...');
                liveagent.init('https://d.la3-c1-ia4.salesforceliveagent.com/chat', '572330000004CuZ', '00D40000000N3ML', true, {
                    onInit: function() {
                        console.log('LiveAgent initialized successfully');
                    }
                });

                window._laq.push(function() {
                    liveagent.showWhenOnline('5734y0000008PTF', document.getElementById('liveagent_button_online_5734y0000008PTF'));
                    liveagent.showWhenOffline('5734y0000008PTF', document.getElementById('liveagent_button_offline_5734y0000008PTF'));
                });
            }

            // Start LiveAgent chat
            function startLiveAgentChat() {
                console.log('Starting LiveAgent chat...');
                liveagent.startChat('5734y0000008PTF');
            }

            // Load MIAW script
            function loadMIAWScript() {
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = 'https://tricore.my.site.com/ESWMIAWWebsiteChat1730713080360/assets/js/bootstrap.min.js';
                script.onload = initEmbeddedMessaging;
                script.onerror = () => {
                    errorContainer.textContent = 'Error loading chat service. Please try again later.';
                };
                document.body.appendChild(script);
            }

            // Initialize Embedded Messaging
            function initEmbeddedMessaging() {
                try {
                    embeddedservice_bootstrap.settings.language = 'en_US';
                    embeddedservice_bootstrap.init(
                        '00D40000000N3ML',
                        'MIAW_Website_Chat',
                        'https://tricore.my.site.com/ESWMIAWWebsiteChat1730713080360',
                        {
                            scrt2URL: 'https://tricore.my.salesforce-scrt.com'
                        }
                    );
                } catch (err) {
                    console.error('Error loading Embedded Messaging:', err);
                    errorContainer.textContent = 'Error initializing chat service. Please try again later.';
                }
            }

            // Add click event listeners
            legacyChatButton.addEventListener('click', () => {
                console.log('Legacy Chat clicked');
                errorContainer.textContent = '';
                startLiveAgentChat();
            });

            miawChatButton.addEventListener('click', () => {
                console.log('MIAW Chat clicked');
                errorContainer.textContent = '';
                loadMIAWScript();
            });

            // Initial fetch of button visibility
            fetchButtonVisibility();
        });
    </script>
</body>
</html>

document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chatToggle');
    const chatWidget = document.getElementById('chatWidget');
    const chatClose = document.getElementById('chatClose');
    const chatBody = document.getElementById('chatBody');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');

    if (!chatToggle) return; // Not logged in

    // Toggle Widget
    chatToggle.addEventListener('click', () => {
        chatWidget.style.display = 'flex';
        chatToggle.style.display = 'none';
        chatInput.focus();
    });

    chatClose.addEventListener('click', () => {
        chatWidget.style.display = 'none';
        chatToggle.style.display = 'flex';
    });

    // Load History
    async function loadHistory() {
        try {
            const response = await fetch('history.php');
            const data = await response.json();
            if (data.success && data.history.length > 0) {
                // Clear initial message if there's history
                chatBody.innerHTML = ''; 
                data.history.forEach(msg => {
                    appendMessage(msg.role, msg.message);
                });
            }
        } catch (err) {
            console.error('Failed to load history', err);
        }
    }

    loadHistory();

    // Hero Button Support
    const heroChatBtn = document.getElementById('heroChatBtn');
    if (heroChatBtn) {
        heroChatBtn.addEventListener('click', () => {
            chatToggle.style.display = 'none';
            chatWidget.style.display = 'flex';
        });
    }

    // Send Message
    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        // Add User Message
        appendMessage('user', message);
        chatInput.value = '';
        
        // Add Loading Placeholder
        const botMsgDiv = appendMessage('assistant', '...');
        let botResponse = '';

        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            // Clear loading placeholder text
            botMsgDiv.textContent = '';
            
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                
                // Keep the last line in buffer as it might be incomplete
                buffer = lines.pop() || '';
                
                for (const line of lines) {
                    if (line.trim() === '') continue;
                    if (line.startsWith('data: ')) {
                        const jsonStr = line.slice(6);
                        if (jsonStr === '[DONE]') continue;
                        try {
                            const json = JSON.parse(jsonStr);
                            if (json.choices && json.choices[0].delta && json.choices[0].delta.content) {
                                const content = json.choices[0].delta.content;
                                botResponse += content;
                                botMsgDiv.innerHTML = marked.parse(botResponse);
                                chatBody.scrollTop = chatBody.scrollHeight;
                            }
                            if (json.error) {
                                botMsgDiv.textContent = "Error: " + json.error;
                            }
                        } catch (e) {
                            console.error('Error parsing JSON chunk', e);
                        }
                    }
                }
            }

        } catch (err) {
            botMsgDiv.textContent = 'Error: ' + err.message;
        }
    }

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.className = `message ${role}`;
        if (role === 'assistant' && text !== '...') {
            div.innerHTML = marked.parse(text);
        } else {
            div.textContent = text;
        }
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight;
        return div;
    }
});

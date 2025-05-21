<!DOCTYPE html>
<html>
<head>
    <title>Real-Time Comments Test</title>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        #comments { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; height: 300px; overflow-y: scroll; }
        .comment { margin-bottom: 10px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
        #comment-form { display: flex; gap: 10px; margin-bottom: 20px; }
        #comment-input { flex-grow: 1; padding: 8px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .status { padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .connected { background: #dff0d8; color: #3c763d; }
        .disconnected { background: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <h1>Real-Time Comments Test</h1>
    <p>Testing Task ID: <strong>{{ $taskId }}</strong></p>
    
    <div id="connection-status" class="status disconnected">
        Disconnected from Socket.IO
    </div>
    
    <div id="comments"></div>
    
    <div id="comment-form">
        <input type="text" id="comment-input" placeholder="Type your comment...">
        <button id="send-button">Send Comment</button>
    </div>
    
    <div>
        <h3>API Test Results</h3>
        <div id="api-test-results"></div>
        <button id="test-api-button">Test API Connection</button>
    </div>

    <script>
        const taskId = "{{ $taskId }}";
        const authToken = "{{ $authToken }}";
    
        const socket = io('http://localhost:3002', {
            transports: ['websocket'],
            auth: {
                token: authToken
            },
            reconnectionAttempts: 5,
            reconnectionDelay: 1000,
        });
    
        socket.on('connect', () => {
            console.log('✅ Connected to Socket.IO');
            document.getElementById('connection-status').className = 'status connected';
            document.getElementById('connection-status').textContent = 'Connected to Socket.IO';
            socket.emit('join_task', taskId);
        });
    
        socket.on('disconnect', () => {
            console.log('❌ Disconnected from Socket.IO');
            document.getElementById('connection-status').className = 'status disconnected';
            document.getElementById('connection-status').textContent = 'Disconnected from Socket.IO';
        })
    

        // Handle new comments
        socket.on('new_comment', (comment) => {
            console.log('New comment received:', comment);
            const commentsDiv = document.getElementById('comments');
            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            commentElement.innerHTML = `
                <strong>${comment.sender_id}</strong>: ${comment.comment}
                <small>${new Date(comment.timestamp).toLocaleString()}</small>
            `;
            commentsDiv.appendChild(commentElement);
            commentsDiv.scrollTop = commentsDiv.scrollHeight;
        });

        // Send comment
        document.getElementById('send-button').addEventListener('click', async () => {
            const input = document.getElementById('comment-input');
            const comment = input.value.trim();
            
            if (comment) {
                try {
                    const response = await fetch(`/api/tasks/${taskId}/comments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            // sender_id: "80c36a53-5314-4b05-a3f2-855b6da03998",
                            // full_name:"AKBAR"
                            comment: comment
                        })
                    });
                    
                    const data = await response.json();
                    console.log('Comment sent:', data);
                    input.value = '';
                } catch (error) {
                    console.error('Error sending comment:', error);
                }
            }
        });

        // Test API connection
        document.getElementById('test-api-button').addEventListener('click', async () => {
            const resultsDiv = document.getElementById('api-test-results');
            resultsDiv.innerHTML = '<p>Testing API connection...</p>';
            
            try {
                // Test GET comments
                const getResponse = await fetch(`/api/tasks/${taskId}/comments`);
                const comments = await getResponse.json();
                
                resultsDiv.innerHTML = `
                    <p><strong>GET /api/tasks/${taskId}/comments:</strong> Success!</p>
                    <p>Received ${comments.length} comments</p>
                    <pre>${JSON.stringify(comments.slice(0, 3), null, 2)}</pre>
                    ${comments.length > 3 ? '<p>... and ' + (comments.length - 3) + ' more</p>' : ''}
                `;
                
                // Display existing comments
                const commentsDiv = document.getElementById('comments');
                commentsDiv.innerHTML = '';
                comments.forEach(comment => {
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment';
                    commentElement.innerHTML = `
                        <strong>${comment.sender_id}</strong>: ${comment.comment}
                        <small>${new Date(comment.timestamp).toLocaleString()}</small>
                    `;
                    commentsDiv.appendChild(commentElement);
                });
                
            } catch (error) {
                resultsDiv.innerHTML = `
                    <p><strong>API Test Failed</strong></p>
                    <p>Error: ${error.message}</p>
                `;
            }
        });
    </script>
</body>
</html>
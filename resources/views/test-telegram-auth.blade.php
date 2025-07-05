<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест Telegram Авторизации</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        button { 
            background: #0088cc; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 5px;
        }
        button:hover { background: #006ba8; }
        .result { 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 5px; 
            margin-top: 15px;
            border-left: 4px solid #0088cc;
        }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        pre { 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 3px; 
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🤖 Тест Telegram Авторизации</h1>
        <p>Эта страница позволяет протестировать функционал переноса документов при авторизации через Telegram.</p>
    </div>

    <div class="card">
        <h2>1. Простой тест переноса документов</h2>
        <p>Найдет временного пользователя с документами, создаст нового авторизованного пользователя и перенесет документы.</p>
        <button onclick="testFullFlow()">🔄 Запустить полный тест</button>
        <div id="fullFlowResult"></div>
    </div>

    <div class="card">
        <h2>2. Тест Telegram авторизации по ID пользователя</h2>
        <p>Введите ID временного пользователя для тестирования авторизации:</p>
        <input type="number" id="userId" placeholder="ID пользователя" style="padding: 8px; margin-right: 10px;">
        <button onclick="testTelegramAuth()">🔗 Тест авторизации</button>
        <div id="telegramAuthResult"></div>
    </div>

    <div class="card">
        <h2>3. Список пользователей</h2>
        <button onclick="loadUsers()">📋 Загрузить список пользователей</button>
        <div id="usersResult"></div>
    </div>

    <script>
        async function testFullFlow() {
            const resultDiv = document.getElementById('fullFlowResult');
            resultDiv.innerHTML = '<p>⏳ Выполняется тест...</p>';
            
            try {
                const response = await fetch('/test/full-flow');
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <h3>✅ Тест успешно выполнен!</h3>
                            <p><strong>Временный пользователь:</strong> ID ${data.temp_user.id} (${data.temp_user.email})</p>
                            <p><strong>Документов было:</strong> ${data.temp_user.documents_before}</p>
                            <p><strong>Авторизованный пользователь:</strong> ID ${data.permanent_user.id} (${data.permanent_user.email})</p>
                            <p><strong>Документов стало:</strong> ${data.permanent_user.documents_after}</p>
                            <p><strong>Перенесено документов:</strong> ${data.documents_transferred}</p>
                            <pre>${JSON.stringify(data.transfer_result, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="result error"><h3>❌ Ошибка:</h3><p>${data.error || 'Неизвестная ошибка'}</p></div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error"><h3>❌ Ошибка запроса:</h3><p>${error.message}</p></div>`;
            }
        }

        async function testTelegramAuth() {
            const userId = document.getElementById('userId').value;
            const resultDiv = document.getElementById('telegramAuthResult');
            
            if (!userId) {
                resultDiv.innerHTML = '<div class="result error"><p>Введите ID пользователя</p></div>';
                return;
            }
            
            resultDiv.innerHTML = '<p>⏳ Тестируем авторизацию...</p>';
            
            try {
                const response = await fetch(`/test/telegram-auth/${userId}`);
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <h3>✅ Тест авторизации выполнен!</h3>
                            <p><strong>Пользователь до:</strong> ${JSON.stringify(data.user_before)}</p>
                            <p><strong>Пользователь после:</strong> ${JSON.stringify(data.user_after)}</p>
                            <p><strong>Количество документов:</strong> ${data.documents_count}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="result error"><h3>❌ Ошибка:</h3><p>${data.error || 'Неизвестная ошибка'}</p></div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error"><h3>❌ Ошибка запроса:</h3><p>${error.message}</p></div>`;
            }
        }

        async function loadUsers() {
            const resultDiv = document.getElementById('usersResult');
            resultDiv.innerHTML = '<p>⏳ Загружаем пользователей...</p>';
            
            try {
                // Простой endpoint для получения списка пользователей (можно создать отдельно)
                resultDiv.innerHTML = `
                    <div class="result">
                        <h3>📋 Для тестирования используйте эти ID:</h3>
                        <p><strong>Временные пользователи (без email или с @auto.user):</strong> 3, 4, 9, 10, 11</p>
                        <p><strong>Постоянные пользователи:</strong> 1, 2, 5, 6, 7, 8, 12</p>
                        <p>Выберите ID временного пользователя и введите в поле выше для тестирования авторизации.</p>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error"><h3>❌ Ошибка:</h3><p>${error.message}</p></div>`;
            }
        }
    </script>
</body>
</html> 
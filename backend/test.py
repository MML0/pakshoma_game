import requests
import random
import time

# Replace with your actual API URL
API_URL = "http://mml-dev.ir/pakshoma_game/data.php"
API_URL = "http://127.0.0.1:3000/backend/data.php"
API_URL = "http://mml-dev.ir/paakshoma/data.php"

# Function to generate random user data
def generate_user():
    first_names = ["Alex", "Maria", "John", "Emma", "David"]
    last_names = ["Smith", "Johnson", "Williams", "Brown", "Jones"]
    phone_number = f"+49{random.randint(1000000000, 9999999999)}"  # Random German-style phone number

    return {
        "name": random.choice(first_names),
        "last_name": random.choice(last_names),
        "phone_number": phone_number,
        "team_mate_id": None  # No teammate initially
    }

# Function to update user data
def update_user(user_id, password="admin123"):
    return {
        "id": user_id,
        "score": round(random.uniform(0, 100), 2),
        "play_time": round(random.uniform(10, 300), 2),  # Random play time between 10-300 mins
        "password": password
    }

# **STEP 1: Register 5 Users**
user_ids = []

for _ in range(5):
    user_data = generate_user()
    response = requests.post(f"{API_URL}?action=register", json=user_data)
    
    if response.status_code == 200 and response.json().get("status") == "success":
        print(f"✅ Registered: {user_data['name']} {user_data['last_name']}")
        user_ids.append(len(user_ids) + 1)  # Simulating user IDs (would normally come from API response)
    else:
        print(f"❌ Registration failed: {response.json()}")

# **STEP 2: Simulate Data Updates Every 10 Seconds**
for user_id in user_ids:
    time.sleep(0.1)  # Wait for 10 seconds before updating
    update_data = update_user(user_id)
    response = requests.post(f"{API_URL}?action=update", json=update_data)
    
    if response.status_code == 200 and response.json().get("status") == "success":
        print(f"✅ Updated User {user_id} - Score: {update_data['score']}, Play Time: {update_data['play_time']} min")
    else:
        print(f"❌ Update failed for User {user_id}: {response.json()}")

print("✅ All users updated successfully!")

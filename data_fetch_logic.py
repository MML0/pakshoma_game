import json
import time  # Corrected spacing

# me - this DAT
# 
# channel - the Channel object which has changed
# sampleIndex - the index of the changed sample
# val - the numeric value of the changed sample
# prev - the previous sample value
# 
# Make sure the corresponding toggle is enabled in the CHOP Execute DAT.

def onOffToOn(channel, sampleIndex, val, prev):
    return

def whileOn(channel, sampleIndex, val, prev):
    return

def onOnToOff(channel, sampleIndex, val, prev):
    json_data = op('json_dat').text  # JSON DAT content
    parsed = json.loads(json_data)

    dat_table = op('data_dat')
    dat_table.clear()

    # Ensure parsed_data is a list, then access the first element safely
    if isinstance(parsed, list) and len(parsed) > 0:
        parsed = parsed[0]  # Extract first dictionary
    else:
        parsed = {"status": "unknown", "message": "No valid data received", "data": []}

    # Add headers
    dat_table.appendRow(['Player', 'Name', 'Last Name', 'Phone', 'Status'])

    if parsed.get('status') == 'success' and isinstance(parsed.get('data'), list) and parsed['data']:
        players = parsed['data']

        # Add each player's data
        for i, player in enumerate(players, 1):
            name = player.get('name', '')
            last_name = player.get('last_name', '')
            phone = player.get('phone_number', '')
            stat = player.get('game_stat', '')
            dat_table.appendRow([f'Player {i}', name, last_name, phone, stat])

        # Pulse the 'ready' trigger if desired
        op('ready_pulse').par.triggerpulse.pulse()  # Pulse a parameter (e.g., a Trigger CHOP)

    else:
        # If no players are available, create empty placeholder rows for clarity
        for i in range(2):  # Creates 5 empty players (adjust as needed)
            dat_table.appendRow([f'Player {i+1}', '', '', '', 'Waiting...'])

    return

def whileOff(channel, sampleIndex, val, prev):
    return

def onValueChange(channel, sampleIndex, val, prev):
    return

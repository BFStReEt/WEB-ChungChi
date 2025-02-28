def convert_chat_to_json(input_file, output_file):
    """
    Convert chat export text file to JSON format
    """
    import json

    try:
        with open(input_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        conversations = content.split('======')
        chat_data = []
        
        for conv in conversations:
            if not conv.strip():
                continue
                
            lines = conv.strip().split('\n')
            header = lines[0].strip()
            
            if not header.startswith('['):
                continue
                
            messages = []
            timestamps = []
            
            for line in lines[1:]:
                line = line.strip()
                if not line or not '[20' in line:
                    continue
                    
                try:
                    if "thumb" in line:
                        continue
                        
                    timestamp = line[line.find('[')+1:line.find(']')]
                    timestamps.append(timestamp)
                    
                    time_end = line.find(']') + 1
                    sender = line[time_end:line.rfind(':')].strip() 
                    message = line[line.rfind(':')+1:].strip()
                    
                    if not message or message.isspace():
                        continue
                        
                    messages.append({
                        'sender': sender,
                        'message': message
                    })
                except:
                    continue

            if messages:
                conversation = {
                    'conversation_id': header,
                    'start_time': min(timestamps) if timestamps else None,
                    'end_time': max(timestamps) if timestamps else None,
                    'messages': messages
                }
                chat_data.append(conversation)
        
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(chat_data, f, ensure_ascii=False, indent=2)
            
        return True, "Conversion successful"
        
    except Exception as e:
        return False, f"Error: {str(e)}"

if __name__ == "__main__":
    input_file = r"C:\xampp\htdocs\chungchi\public\input.txt"  # Note: .txt.txt extension
    output_file = r"C:\xampp\htdocs\chungchi\public\chat_data.json"
    
    success, message = convert_chat_to_json(input_file, output_file)
    print(message)
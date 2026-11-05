import json

with open('lh.json') as f:
    data = json.load(f)

for audit_id in ['uses-rel-preload', 'network-requests']:
    if audit_id in data['audits']:
        a = data['audits'][audit_id]
        score = a['score']
        title = a['title']
        print(f"=== {audit_id} === score={score} title={title}")
        if 'details' in a and a['details']:
            items = a['details'].get('items', [])
            for it in items[:20]:
                url = it.get('url', 'N/A')
                start = it.get('startTime', 'N/A')
                end = it.get('endTime', 'N/A')
                duration = it.get('duration', 'N/A')
                print(f'  {url} | start={start} end={end} duration={duration}')
        print()

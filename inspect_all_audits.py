import json

with open('lh.json') as f:
    data = json.load(f)

# Print all audits with score < 1 and their details
for audit_id, a in data['audits'].items():
    score = a.get('score')
    if score is not None and score < 1:
        print(f"=== {audit_id} === score={score} title={a.get('title')}")
        if 'details' in a and a['details']:
            items = a['details'].get('items', [])
            for it in items[:3]:
                print(f"  {it}")
        print()

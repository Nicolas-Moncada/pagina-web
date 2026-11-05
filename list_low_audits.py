import json

with open('lh.json') as f:
    data = json.load(f)

audits = data['audits']
for key in audits:
    a = audits[key]
    score = a.get('score')
    if score is not None and score < 1:
        print(key, 'score=', score, 'display=', a.get('displayValue', 'N/A'))

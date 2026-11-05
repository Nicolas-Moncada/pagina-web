import json

with open('lh.json') as f:
    r = json.load(f)
audits = r['audits']

for key in ['largest-contentful-paint', 'first-contentful-paint', 'render-blocking-resources', 'font-display', 'network-requests']:
    if key in audits:
        a = audits[key]
        score = a.get('score')
        title = a.get('title')
        print(key + ': score=' + str(score) + ' title=' + str(title))
        if a.get('details'):
            items = a['details'].get('items', [])
            for it in items[:8]:
                url = it.get('url') or it.get('request', {}).get('url', '')
                tb = it.get('totalBytes', '')
                dur = it.get('durationMs', '')
                print('   ' + str(url) + ' bytes=' + str(tb) + ' dur=' + str(dur))

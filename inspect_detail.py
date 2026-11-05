import json

with open('lh.json') as f:
    data = json.load(f)

audits_to_check = [
    'largest-contentful-paint',
    'first-contentful-paint',
    'speed-index',
    'render-blocking-resources',
    'font-display',
    'image-aspect-ratio',
    'crawlable-anchors',
    'uses-long-cache-ttl',
    'network-requests',
    'uses-optimized-images',
    'offscreen-images',
    'mainthread-tasks',
]

for audit_id in audits_to_check:
    if audit_id in data['audits']:
        a = data['audits'][audit_id]
        print(f'=== {audit_id} === score={a["score"]} title={a["title"]}')
        if 'details' in a and a['details']:
            items = a['details'].get('items', [])
            for it in items[:5]:
                snippet = it.get('node', {}).get('snippet', str(it)[:200])
                print('  ', snippet)
        print()

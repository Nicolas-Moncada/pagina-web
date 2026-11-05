import json

with open('lh.json') as f:
    r = json.load(f)
audits = r['audits']

# LCP element details
if 'largest-contentful-paint' in audits:
    a = audits['largest-contentful-paint']
    if a.get('details'):
        items = a['details'].get('items', [])
        for it in items:
            print('LCP node:', it.get('node', {}).get('type', ''), it.get('node', {}).get('selector', ''))
            print('  loadTime:', it.get('loadTime', ''))
            print('  renderTime:', it.get('renderTime', ''))
            print('  url:', it.get('url', ''))
            print()

# LCP sub-parts
for key in ['lcp', 'lcp-critical-request-chain', 'critical-request-chains']:
    if key in audits:
        a = audits[key]
        print(key + ': score=' + str(a.get('score')))
        if a.get('details'):
            print('  details keys:', list(a['details'].keys())[:10])
            items = a['details'].get('items', [])
            for it in items[:10]:
                print('   ', it)

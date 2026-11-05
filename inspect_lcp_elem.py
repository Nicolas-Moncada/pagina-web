import json

with open('lh.json') as f:
    data = json.load(f)

lcp_elem = data['audits'].get('largest-contentful-paint-element', {})
print(f"score: {lcp_elem.get('score')}")
if lcp_elem and 'details' in lcp_elem and lcp_elem['details']:
    items = lcp_elem['details'].get('items', [])
    for it in items:
        node = it.get('node', {})
        print(f"snippet: {node.get('snippet', 'N/A')[:200]}")
        print(f"rect: {node.get('boundingRect', 'N/A')}")
        print()

# Also check render-blocking resources
rb = data['audits'].get('render-blocking-resources', {})
print(f"Render-blocking score: {rb.get('score')}")
if rb and 'details' in rb and rb['details']:
    for it in rb['details'].get('items', []):
        print(f"url: {it.get('url', 'N/A')}")
        print(f"  totalBytes: {it.get('totalBytes', 'N/A')}")
        print(f"  wastedMs: {it.get('wastedMs', 'N/A')}")
        print(f"  completionTime: {it.get('completionTime', 'N/A')}")

#!/usr/bin/env python3
"""
Update GoDaddy DNS records for aqssat.co
=========================================
Points jadal and namaa subdomains to the new Contabo server.
"""
import requests
import json
import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))
from credentials import GODADDY_API_KEY, GODADDY_API_SECRET, GODADDY_DOMAIN, NEW_SERVER_IP

API_BASE = 'https://api.godaddy.com/v1'
HEADERS = {
    'Authorization': f'sso-key {GODADDY_API_KEY}:{GODADDY_API_SECRET}',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}

SUBDOMAINS_TO_UPDATE = [
    'jadal',
    'namaa',
    'vite.jadal',
    'vite.namaa',
]


def get_all_records():
    """Fetch all DNS records for the domain."""
    url = f'{API_BASE}/domains/{GODADDY_DOMAIN}/records'
    resp = requests.get(url, headers=HEADERS)
    resp.raise_for_status()
    return resp.json()


def get_record(record_type, name):
    """Fetch a specific DNS record."""
    url = f'{API_BASE}/domains/{GODADDY_DOMAIN}/records/{record_type}/{name}'
    resp = requests.get(url, headers=HEADERS)
    if resp.status_code == 404:
        return None
    resp.raise_for_status()
    data = resp.json()
    return data[0] if data else None


def set_record(record_type, name, value, ttl=600):
    """Create or update a DNS record."""
    url = f'{API_BASE}/domains/{GODADDY_DOMAIN}/records/{record_type}/{name}'
    payload = [{'data': value, 'ttl': ttl}]
    resp = requests.put(url, headers=HEADERS, json=payload)
    resp.raise_for_status()
    return True


def main():
    print(f"\n{'=' * 55}")
    print(f"  GoDaddy DNS Update - {GODADDY_DOMAIN}")
    print(f"  Target IP: {NEW_SERVER_IP}")
    print(f"{'=' * 55}\n")

    # Phase 1: Show current DNS records
    print("--- Current DNS Records ---\n")
    try:
        records = get_all_records()
    except requests.exceptions.HTTPError as e:
        print(f"  ERROR: Failed to fetch DNS records: {e}")
        print(f"  Response: {e.response.text if e.response else 'N/A'}")
        sys.exit(1)

    a_records = [r for r in records if r['type'] == 'A']
    for r in sorted(a_records, key=lambda x: x['name']):
        marker = ' <-- UPDATE' if r['name'] in SUBDOMAINS_TO_UPDATE else ''
        print(f"  A  {r['name']:30s}  ->  {r['data']}{marker}")

    print()

    # Phase 2: Update DNS records
    print("--- Updating DNS Records ---\n")
    results = []

    for sub in SUBDOMAINS_TO_UPDATE:
        current = get_record('A', sub)
        current_ip = current['data'] if current else 'NOT SET'

        if current_ip == NEW_SERVER_IP:
            print(f"  {sub}.{GODADDY_DOMAIN}  ->  {NEW_SERVER_IP}  (already correct)")
            results.append((sub, 'SKIP', current_ip))
            continue

        print(f"  {sub}.{GODADDY_DOMAIN}  ->  {current_ip}  =>  {NEW_SERVER_IP}  ...", end='', flush=True)
        try:
            set_record('A', sub, NEW_SERVER_IP, ttl=600)
            print("  OK")
            results.append((sub, 'UPDATED', current_ip))
        except requests.exceptions.HTTPError as e:
            print(f"  FAILED: {e}")
            print(f"    Response: {e.response.text if e.response else 'N/A'}")
            results.append((sub, 'FAILED', current_ip))

    # Phase 3: Verify
    print("\n--- Verification ---\n")
    for sub in SUBDOMAINS_TO_UPDATE:
        record = get_record('A', sub)
        ip = record['data'] if record else 'NOT FOUND'
        status = 'OK' if ip == NEW_SERVER_IP else 'MISMATCH'
        print(f"  {sub}.{GODADDY_DOMAIN}  ->  {ip}  [{status}]")

    # Summary
    print(f"\n{'=' * 55}")
    print("  SUMMARY")
    print(f"{'=' * 55}")
    updated = sum(1 for _, s, _ in results if s == 'UPDATED')
    skipped = sum(1 for _, s, _ in results if s == 'SKIP')
    failed = sum(1 for _, s, _ in results if s == 'FAILED')
    print(f"  Updated: {updated}  |  Already correct: {skipped}  |  Failed: {failed}")

    if updated > 0:
        print(f"\n  DNS propagation takes 5-30 minutes (TTL=600s).")
        print(f"  After propagation, generate SSL certificates:")
        print(f"    certbot --apache -d jadal.aqssat.co")
        print(f"    certbot --apache -d namaa.aqssat.co")

    print(f"{'=' * 55}\n")


if __name__ == '__main__':
    main()

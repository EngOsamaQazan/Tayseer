import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko
import urllib.parse

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Search by name
search = urllib.parse.quote('التوايهه')
print('=== Testing api.php with name search ===')
cmd = f'curl -sL "https://jadal.aqssat.co/fahras/api.php?db=jadal&token=b83ba7a49b72&action=search&search={search}"'
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode()
err = stderr.read().decode()
print('Length:', len(out))

if '"images"' in out:
    print('>>> images field FOUND!')
    idx = out.find('"images"')
    print(out[max(0,idx-100):idx+800])
elif '"attachments"' in out:
    print('>>> attachments found but no images field')
    idx = out.find('"attachments"')
    print(out[max(0,idx-100):idx+200])
else:
    print('>>> No results or error')
    print(out[:2000])

if err:
    print('STDERR:', err[:500])

# Also test relations
print('\n=== Testing relations.php ===')
cmd2 = 'curl -sL "https://jadal.aqssat.co/fahras/relations.php?db=jadal&token=b83ba7a49b72&client=9348&format=json"'
stdin, stdout, stderr = ssh.exec_command(cmd2)
out2 = stdout.read().decode()
print('Length:', len(out2))
if '"images"' in out2:
    print('>>> images field FOUND!')
    idx = out2.find('"images"')
    print(out2[max(0,idx-50):idx+500])
else:
    print(out2[:2000])

ssh.close()
print('\nDone!')

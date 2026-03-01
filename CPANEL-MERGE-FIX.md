# Fix cPanel Git merge error (drivers.json)

The error occurs because the **server** has local changes to `admin/data/drivers.json`. Since we use the database now, that file is no longer needed.

## Via SSH or cPanel Terminal

```bash
cd /home/no5tyreandmotco/public_html/test

# Discard local changes to drivers.json (we don't use it anymore)
git checkout -- admin/data/drivers.json

# Or if the file shouldn't exist at all, remove it and stage the deletion
rm -f admin/data/drivers.json
git add admin/data/drivers.json

# Now pull/merge
git pull origin main
```

## Alternative: Stash then merge

```bash
cd /home/no5tyreandmotco/public_html/test
git stash
git pull origin main
git stash drop
```

Use the first approach if you're sure you want to keep the database-only setup (no `drivers.json`).

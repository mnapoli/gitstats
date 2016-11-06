# Iterate through git commits

**Work in progress**

## Configuration

Configuration example (`conf.yml`):

```yaml
repository: https://github.com/mnapoli/silly
tasks:
    # Finds the number of files in the repository
    myapp.files: "find . -maxdepth 8 -type f | wc -l | xargs"
```

The repository is cloned and all tasks are run for each commit.

## Usage

```
php app.php run
```

The git repository will be cloned in a `repository` folder, and tasks will be run against each commit.

The output will look like this:

```
files: 47 on commit 471677f5c8a0753d38b25e43e750148ddeafd885 (15 Feb 2015)
files: 41 on commit 96c8a7a53a288f89d8e9e85189604f248f5f19bb (12 Feb 2015)
files: 38 on commit ff6f1e3b6136ee9772de6a18012f14aef51f62c3 (09 Feb 2015)
files: 34 on commit 616f723a7bd1022c52cfe589d81319b982ee2452 (07 Feb 2015)
...
```

## Usage with Graphite

You can use Graphite to store the data and create graphs. You can run Graphite simply with Docker:

```
./run-graphite.sh
```

Then use the `--graphite` option and pipe that to port 2003 (Graphite) using netcat:

```
php app.php run --graphite | nc localhost -c 2003
```

The output piped to Graphite will look like this:

```
silly.files 47 1423733092
silly.files 41 1423732866
silly.files 38 1423731175
silly.files 38 1423730802
silly.files 34 1423730707
...
```

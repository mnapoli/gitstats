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

## Usage with Graphite

You can use Graphite to store the data and create graphs. You can run Graphite simply with Docker:

```
./run-graphite.sh
```

Then use the `--graphite` option and pipe that to port 2003 (Graphite) using netcat:

```
php app.php run --graphite | nc localhost -c 2003
```

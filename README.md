# Iterate through git commits to gather statistics

## Installation

- clone this repository
- install Composer dependencies
- symlink the `gitstats` to your `/usr/local/bin`: `ln -s /home/<you>/code/gitstats/gitstats /usr/local/bin/gitstats`

Alternatively you can install the Composer package globally (`composer global require mnapoli/gitstats`)

## Usage

- Add a `.gitstats.yml` file in your current directory:

```yaml
tasks:
    'Commit message': "git log -1 --pretty=%B | head -n 1"
    'Commit author': "git log -1 --pretty=%an"
    'Number of files': "find . -type f | wc -l | xargs"
    'Number of directories': "find . -type d | wc -l | xargs"
```

- Run the application:

```shell
$ gitstats run <git-repository-url>
```

The repository will be cloned in a temporary directory. All tasks will be run against each commit. Ensure the repository doesn't contain modifications.

The output is formatted as CSV:

```csv
Commit,Date,Number of files,Number of directories
d612a29fae3b0f625b9be819802e93214d4eecd9,2016-08-31T12:55:38+02:00,61,28
497f22a27896d146a35660f452eba24d3a14db3f,2016-08-31T12:53:01+02:00,61,28
fc0646f236e6bb0a10b14a67424f932f28eb1062,2016-08-26T19:29:40+02:00,62,28
221528e63d7aac3aa247dfde191b5f6c380cbb7e,2016-08-25T01:28:55+02:00,62,28
...
```

You can write the output to a file:

```shell
$ gitstats run <git-repository-url> > results.csv
```

You can then import that into a database or open it up with Excel or whatever.

### MySQL

You can output the result as SQL queries to insert/update a MySQL table:

```shell
$ gitstats run <git-repository-url> --format=sql | mysql -u <user> -p <table>
```

### Limit the number of commits processed

You can limit the number of commits to process using the `--max` parameter:

```shell
# Process only 100 commits
$ gitstats run <git-repository-url> --max=100
```

### Show the progress

You can show a progress bar on stderr using the `--progress` parameter. When using that parameter it makes sense to redirect the output to a file or another command:

```shell
$ gitstats run <git-repository-url> --progress > file.csv
```

![](https://i.imgur.com/zoKRker.png)

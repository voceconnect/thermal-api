set :application, 'thermal-api'
set :repo_url, "git@github.com:voceconnect/#{fetch(:application)}.git"

set :scm, 'git-to-svn'
set :type, 'plugin'

set :svn_repository, "http://plugins.svn.wordpress.org/#{fetch(:application)}/"
set :svn_deploy_to, "trunk"

## additional files to exclude from committing to svn
set :build_files, (
  fetch(:build_files) << %w{
    phpunit.xml*
  }
).flatten

set :build_folders, (
  fetch(:build_folders) << %w{
  	config
  	bin
  	tests
  }
).flatten
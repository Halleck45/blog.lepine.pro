set :application, "Blog Lepine"

set :repository, "./build"

set :scm, :none
set :deploy_via, :copy
set :copy_compression,    :gzip
set :keep_releases,       5

set :deploy_to, '/home/data/www/lepine/pro/blogv2/'
set :user, 'bloglepine-deploy'
set :use_sudo, false

role :web, "blog.lepine.pro"
role :app, "blog.lepine.pro"

set :normalize_asset_timestamps, false # pas de public/images, public/css...


after "deploy:restart", "deploy:cleanup"


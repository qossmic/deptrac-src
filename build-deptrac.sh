#!/usr/bin/env bash

DEPTRAC_DIR="${DEPTRAC_DIR:-../deptrac-scoped}"
BUILD_DIR=build
BUILD_TMP=${BUILD_DIR}/deptrac-build
PHP='docker compose exec -u 1000 deptrac php -d memory_limit=-1'
CONTAINER='docker compose exec -u 1000 deptrac bash'
SCOPER=$BUILD_DIR/php-scoper.phar
BOX=$BUILD_DIR/box.phar

echo $DEPTRAC_DIR
echo $BUILD_DIR
echo $BUILD_TMP

info()
{
    MESSAGE=$1;
    echo "######### $MESSAGE ########";
}

info "Start build deptrac"
rm -rf $BUILD_TMP

info "Install composer"
$PHP /usr/bin/composer install -a --no-dev

info "Scope deptrac"
$PHP $SCOPER add-prefix --force --config scoper.inc.php --working-dir . --output-dir $BUILD_TMP

# info "build phar"
$PHP $BOX compile

# info "sign phar"
gpg --detach-sign --armor --local-user 974E9033414D7F2BC9FE1E6AD4F06E96D1BD037B --output $BUILD_TMP/deptrac.phar.asc $BUILD_TMP/deptrac.phar
gpg --verify $BUILD_TMP/deptrac.phar.asc $BUILD_TMP/deptrac.phar

info "Dump Composer Autoloader"
$PHP /usr/bin/composer dump-autoload --working-dir $BUILD_TMP -a --no-dev

info "Copy package templates"
$CONTAINER cp -rv $BUILD_DIR/template/* *.md mkdocs.yml docs -t $BUILD_TMP
$CONTAINER cp -rv $BUILD_DIR/template/.github -t $BUILD_TMP
$CONTAINER cp -rv $BUILD_DIR/template/.gitignore -t $BUILD_TMP

info "Copy build into deptrac distrubtion repository"
cp -rv $BUILD_TMP/* $DEPTRAC_DIR 

# info "Git commit changes"
# echo "Update $(date)" > git_commit_message.txt
# echo "" >> git_commit_message.txt
# git log $(git describe --tags --abbre=0)..HEAD --oneline >> git_commit_message.txt
#
# git -C $DEPTRAC_DIR add .
# mv git_commit_message.txt $DEPTRAC_DIR
# git -C $DEPTRAC_DIR commit  -F git_commit_message.txt
# git -C $DEPTRAC_DIR reset --hard
# git -C $DEPTRAC_DIR clean -fd

info "Build done!"

$PHP /usr/bin/composer install

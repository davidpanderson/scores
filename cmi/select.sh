#! /bin/sh

case $1 in
    'prod')
        rm -f data
        ln -s data_prod data
        rm -f db.json
        ln -s db_prod.json db.json
    ;;
    'dev')
        rm -f data
        ln -s data_dev data
        rm -f db.json
        ln -s db_dev.json db.json
    ;;
    *)
        echo "usage"
    ;;
esac

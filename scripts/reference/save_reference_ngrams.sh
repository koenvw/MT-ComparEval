#!/bin/sh

dir=`dirname $0`
sourceFile=$1
experimentId=$2
database=$3

mkdir /tmp/$$

perl -I $dir/../perl $dir/../perl/generate_ngrams_sql.pl $sourceFile > /tmp/$$/ngrams
split --lines 500 /tmp/$$/ngrams /tmp/$$/ngrams-chunks

for file in /tmp/$$/ngrams-chunks*
do
	values=`sed "s/SELECT /SELECT $experimentId AS \"experiment_id\",/g;2,$ s/^/UNION ALL /;" $file`
 	echo "INSERT INTO \"reference_ngrams\" ( \"experiment_id\", \"text\", \"sentence_id\", \"length\", \"position\", \"nth\") ${values};" > /tmp/$$/sql

	sqlite3 $database < /tmp/$$/sql
done

rm -rf /tmp/$$ 

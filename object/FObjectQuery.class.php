<?php
class FObjectQuery {
	public static function select ($type) {
		return new FObjectQueryBuilder($type);
	}

	public static function getStructure () {
		return array(
			'attributes' => array(
				'_prefix' => "attribute_",
				'_engine' => "InnoDB",
				'id' => FDataModel::bigintPK(),
				'object_id' => FDataModel::bigintFK()->foreignKey('objects', 'object_id', 'CASCADE', 'CASCADE'),
				'user_id' => FDataModel::bigintFK(),
				'key' => FDataModel::varchar(64)->notNull()->index(),
				'value' => FDataModel::longtext(),
				'added' => FDataModel::timestamp()->insertOnly(),
				'archived' => FDataModel::tinyint(1)->notNull()->def(0)->index(),
				'preview' => FDataModel::tinyint(1)->notNull()->def(0)
			),
			'objects' => array(
				'_prefix' => "object_",
				'_engine' => "InnoDB",
				'id' => FDataModel::bigintPK(),
				'type' => FDataModel::varchar(64)->notNull()->index(),
				'cache' => FDataModel::longtext(),
				'deleted' => FDataModel::tinyint(1)->def(0)
			)
		);
	}

	public static function updateStructure () {
		sync_database(self::getStructure());
	}
}

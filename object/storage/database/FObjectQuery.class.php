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
				'object_id' => FDataModel::intFK()->foreignKey('objects', 'object_id', 'CASCADE', 'CASCADE'),
				'creator_id' => FDataModel::int()->unsigned()->foreignKey('objects', 'object_id', 'CASCADE', 'SET NULL'),
				'key' => FDataModel::varchar(64)->notNull()->index(),
				'value' => FDataModel::blob(),
				'added' => FDataModel::timestamp()->insertOnly(),
				'archived' => FDataModel::tinyint(1)->notNull()->def(0)->index(),
				'preview' => FDataModel::tinyint(1)->notNull()->def(0)
			),
			'object_caches' => array(
				'_engine' => "InnoDB",
				'object_id' => FDataModel::intFK()->foreignKey('objects', 'object_id', 'CASCADE', 'CASCADE')->unique(),
				'cache' => FDataModel::blob()
			),
			'object_links' => array(
				'_engine' => "InnoDB",
				'origin_id' => FDataModel::int()->unsigned()->unique('object_link')
					->foreignKey('objects', 'object_id', 'CASCADE', 'CASCADE'),
				'target_id' => FDataModel::int()->unsigned()->unique('object_link')
					->foreignKey('objects', 'object_id', 'CASCADE', 'CASCADE'),
				'added' => FDataModel::timestamp()->insertOnly()
			),
			'objects' => array(
				'_prefix' => "object_",
				'_engine' => "InnoDB",
				'id' => FDataModel::intPK(),
				'parent_id' => FDataModel::int()->unsigned()->foreignKey('objects', 'object_id', 'CASCADE', 'SET NULL'),
				'creator_id' => FDataModel::int()->unsigned()->foreignKey('objects', 'object_id', 'CASCADE', 'SET NULL'),
				'type' => FDataModel::varchar(64)->notNull()->index(),
				'added' => FDataModel::timestamp()->insertOnly(),
				'deleted' => FDataModel::tinyint(1)->def(0),
				'preview' => FDataModel::enum('NONE', 'PARTIAL', 'ALL')->notNull(),
			)
		);
	}

	public static function updateStructure () {
		sync_database(self::getStructure());
	}
}

<?php namespace BoundedContext\Laravel\Sourced\Aggregate\State\Snapshot;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Factory as StateSnapshotFactory;
use BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Snapshot;
use BoundedContext\Laravel\Illuminate\Projection\AbstractQueryable;
use Illuminate\Contracts\Foundation\Application;

class Repository extends AbstractQueryable implements \BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Repository
{
    protected $state_snapshot_factory;
    protected $table = 'snapshots_aggregate_state';

    public function __construct(Application $app, StateSnapshotFactory $state_snapshot_factory)
    {
        parent::__construct($app);

        $this->state_snapshot_factory = $state_snapshot_factory;
    }

    public function id(Identifier $id)
    {
        $snapshot_row = $this->query()
            ->where('id', $id->serialize())
            ->first()
        ;

        $snapshot_row = (array) $snapshot_row;

        if(!$snapshot_row)
        {
            return $this->state_snapshot_factory->create($id);
        }

        $snapshot_row['state'] = json_decode($snapshot_row['state'], true);

        return $this->state_snapshot_factory->tree(
            $snapshot_row
        );
    }

    public function save(Snapshot $snapshot)
    {
        $this->query()->getConnection()->statement(
          'INSERT INTO ' . $this->table .
          ' (id, occurred_at, version, state) ' .
            'VALUES( '
                . '\'' . $snapshot->id()->serialize() . '\','
                . '\'' . $snapshot->occurred_at()->serialize() . '\','
                . '\'' . $snapshot->version()->serialize() . '\','
                . '\'' . json_encode($snapshot->schema()->serialize()) . '\'' .
            ') ' .
          'ON DUPLICATE KEY UPDATE ' .
            'occurred_at = \'' . $snapshot->occurred_at()->serialize() . '\', ' .
            'version = \'' . $snapshot->version()->serialize() . '\', ' .
            'state = \'' . json_encode($snapshot->schema()->serialize()) . '\''
        );
    }
}

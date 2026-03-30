final class SyncTagsCommand extends Command
{
    protected $signature =
   'tags:sync { note : The Identifier for the note. }';
    protected $description =
                    'Sync tags attached to this note.';

    public function handle(Client $client): int
    {
        // work on collecting the tags to sync.
        // The tags will be stored as $tags
        // We have the argument from the CLI as
        //      $note for the identifier
        try {
            $client->notes()->tags($note)->sync($tags);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
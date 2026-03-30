<html>
<style>
    .row { display: flex; flex-wrap: wrap}
    .card {
        border: 1px solid #888;
        padding: 1rem;
        min-width: 220px;
        margin: 0.5rem;
        border-radius: 0.5rem;
    }
    span { color: #999}
    ul {padding: 0 0 0 1rem}
</style>
<body>
<h1>Draws</h1>
<div class="row">
    <?php
    foreach ($done as $group) {
        ?>
        <div class="card">
            <h3><?= $group->label ?></h3>
            <ul>
                <?php foreach ($group->getTeams() as $team) { ?>
                    <li><?= $team->name ?> <span>(<?= $team->region ?>)</span></li>
                <?php } ?>
            </ul>
        </div>
        <?php
    }
    ?>
</div>
</body></html>

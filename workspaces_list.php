<?php
include "connection.php";

$select_ws = "SELECT workspaces.*, 
                     zone.zone_name, 
                     rooms.images, 
                     COALESCE(AVG(reviews.rating), 0) AS avg_rating
              FROM `workspaces` 
             LEFT JOIN `rooms` ON `workspaces`.`workspace_id` = `rooms`.`workspace_id`
              LEFT JOIN `zone` ON `workspaces`.`zone_id` = `zone`.`zone_id`
              LEFT JOIN `bookings` ON `workspaces`.`workspace_id` = `bookings`.`workspace_id`
              LEFT JOIN `reviews` ON `bookings`.`booking_id` = `reviews`.`booking_id`
              GROUP BY workspaces.workspace_id";
$run_select_ws = mysqli_query($connect, $select_ws);


$order_by = "workspaces.workspace_id"; // Default sorting
if (!empty($_POST['sort'])) {
    $sort_option = $_POST['sort'];
    if ($sort_option == 'highest_price') {
        $order_by = "`price/hr` DESC";
    } elseif ($sort_option == 'lowest_price') {
        $order_by = "`price/hr` ASC";
    } elseif ($sort_option == 'highest_rating') {
        $order_by = "avg_rating DESC";
    }
}

$select_ws = "SELECT workspaces.*, 
                    zone.zone_name, 
                    rooms.images, 
                     COALESCE(AVG(reviews.rating), 0) AS avg_rating
              FROM `workspaces` 
             JOIN `rooms` ON `workspaces`.`workspace_id` = `rooms`.`workspace_id`
              LEFT JOIN `zone` ON `workspaces`.`zone_id` = `zone`.`zone_id`
              LEFT JOIN `bookings` ON `workspaces`.`workspace_id` = `bookings`.`workspace_id`
              LEFT JOIN `reviews` ON `bookings`.`booking_id` = `reviews`.`booking_id`
              GROUP BY workspaces.workspace_id
              ORDER BY $order_by";
$run_select_ws = mysqli_query($connect, $select_ws);

$run_select_search = null;
if (isset($_POST['search']) && !empty($_POST['text'])) {
    $text = mysqli_real_escape_string($connect, $_POST['text']);
    $select_search = "SELECT workspaces.*, 
                           zone.zone_name, 
                           rooms.images, 
                     COALESCE(AVG(reviews.rating), 0) AS avg_rating
              FROM `workspaces` 
             JOIN `rooms` ON `workspaces`.`workspace_id` = `rooms`.`workspace_id`
              LEFT JOIN `zone` ON `workspaces`.`zone_id` = `zone`.`zone_id`
              LEFT JOIN `bookings` ON `workspaces`.`workspace_id` = `bookings`.`workspace_id`
              LEFT JOIN `reviews` ON `bookings`.`booking_id` = `reviews`.`booking_id`
                  WHERE (`workspaces`.`name` LIKE '%$text%') 
                     OR (`workspaces`.`location` LIKE '%$text%') 
                     OR (`zone`.`zone_name` LIKE '%$text%') 
                  GROUP BY workspaces.workspace_id
                  ORDER BY $order_by";
    $run_select_search = mysqli_query($connect, $select_search);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workspace Listings</title>
    <link rel="stylesheet" href="css/workspaces.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <style>
        /* Center search and sort */
        .search-sort-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            /* Space between elements */
            margin: 30px auto;
            /* Add spacing */
            max-width: 60%;
        }

        .search-sort-container input,
        .search-sort-container select {
            width: 100%;
            max-width: 250px;
            /* Set a max width */
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        /* Adjust workspace cards */
        .container {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .workspace-card {
            width: 300px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .workspace-card:hover {
            transform: translateY(-5px);
        }

        .card-body h2 {
            font-size: 18px;
            font-weight: bold;
        }

        .card-body h3,
        .card-body h4,
        .card-body p {
            font-size: 14px;
        }
    </style>
</head>

<body>

    <!-- Search & Sort Container -->
    <div class="search-sort-container">
        <form method="post" class="d-flex w-100">
            <input class="form-control" type="search" id="searchText" name="text" placeholder="Search by name or zone">
        </form>
        <form method="post">
            <select class="form-select" name="sort" id="sort" onchange="this.form.submit()">
                <option value="">Sort By</option>
                <option value="highest_price">Highest Price</option>
                <option value="lowest_price">Lowest Price</option>
                <option value="highest_rating">Highest Rating</option>
            </select>
        </form>
    </div>

    <div class="container">
        <?php
        $result_set = isset($_POST['search']) ? $run_select_search : $run_select_ws;
        if ($result_set && mysqli_num_rows($result_set) > 0) {
            foreach ($result_set as $index => $row) {
                $carouselId = "carouselExampleIndicators" . $index; ?>
                <div class="workspace-card">
                    <div class="card">
                        <?php
                        $workspace_id = $row['workspace_id'];
                        $img_query = "SELECT `images` FROM `rooms` WHERE `workspace_id` = '$workspace_id'";
                        $run_img = mysqli_query($connect, $img_query); ?>

                        <div id="<?php echo $carouselId; ?>" class="carousel slide">
                            <div class="carousel-inner">
                                <?php
                                $first = true;
                                while ($imag = mysqli_fetch_assoc($run_img)) {
                                    ?>
                                    <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                                        <img src="./img/<?php echo $imag['images']; ?>" class="d-block w-100">
                                    </div>
                                    <?php
                                    $first = false;
                                }
                                ?>
                            </div>


                            <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                            <h3><?php echo htmlspecialchars($row['zone_name']); ?></h3>
                            <h4>Price/hr: <?php echo htmlspecialchars($row['price/hr']); ?> EGP</h4>
                            <p class="rating">Rating: <?php echo number_format($row['avg_rating'], 1); ?> / 5</p>
                        </div>
                    </div>
                </div>
            <?php }
        } else {
            echo "<p class='text-center'>No workspaces found.</p>";
        }
        ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
        $(document).ready(function () {
            $("#searchText").on("input", function () {
                var searchText = $(this).val();
                if (searchText === "") {
                    location.reload();
                    return;
                }

                $.ajax({
                    url: "workspaces_list.php",
                    type: "POST",
                    data: { text: searchText, search: true },
                    success: function (data) {
                        var results = $(data).find('.workspace-card');
                        $('.container').html(results);
                    }
                });
            });
        });
    </script>

</body>

</html>
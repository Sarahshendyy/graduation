<?php
include "../admin/sidebar.php";

if (!isset($_SESSION['user_id'])) {
    die("Session not set! <script>window.location.href='login.php';</script>");
}

$owner_id = $_SESSION['user_id'];
$image_dir = "img/";

$rooms_query = "SELECT r.*, rt.type_name, GROUP_CONCAT(a.amenity SEPARATOR ', ') AS amenities 
                FROM `rooms` r 
                LEFT JOIN `amenities` a ON r.room_id = a.room_id
                LEFT JOIN `room_types` rt ON r.type_id = rt.type_id
                WHERE r.workspace_id IN 
                (SELECT `workspace_id` FROM `workspaces` WHERE `user_id` = '$owner_id')
                GROUP BY r.room_id";
$rooms_result = mysqli_query($connect, $rooms_query);

$room_types_query = "SELECT * FROM `room_types`";
$room_types_result = mysqli_query($connect, $room_types_query);

if (isset($_POST['room_name'])) {
    $room_name = mysqli_real_escape_string($connect, $_POST['room_name']);
    $seats = mysqli_real_escape_string($connect, $_POST['seats']);
    $type_id = mysqli_real_escape_string($connect, $_POST['type_id']);
    $price = mysqli_real_escape_string($connect, $_POST['price']);
    $room_status = mysqli_real_escape_string($connect, $_POST['room_status']);

    $workspace_query = "SELECT workspace_id FROM workspaces WHERE user_id = '$owner_id' LIMIT 1";
    $workspace_result = mysqli_query($connect, $workspace_query);

    if ($workspace_result && mysqli_num_rows($workspace_result) > 0) {
        $workspace_row = mysqli_fetch_assoc($workspace_result);
        $workspace_id = $workspace_row['workspace_id'];

        $image_paths = [];

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                $tmp_name = $_FILES['images']['tmp_name'][$key];

                if (!empty($tmp_name)) {
                    $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_ext;
                    $target_file = $image_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $image_paths[] = $new_filename;
                    }
                }
            }
        }

        $images = implode(",", $image_paths);

        $insert_query = "INSERT INTO rooms (workspace_id, room_name, seats, type_id, `p/hr`, room_status, images) 
                         VALUES ('$workspace_id', '$room_name', '$seats', '$type_id', '$price', '$room_status', '$images')";
        
        if (mysqli_query($connect, $insert_query)) {
            echo "<script>window.location.href='rooms_table.php?added=1';</script>";
            exit();
        } else {
            echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error adding room: " . addslashes(mysqli_error($connect)) . "'
                    });
                  </script>";
        }
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No workspace found for this user.'
                });
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rooms Management</title>
    <link rel="stylesheet" href="css/rooms_tables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h2>Rooms Management</h2>
    
    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
        + Add Room
    </button>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Room Name</th>
                    <th>Seats</th>
                    <th>Type</th>
                    <th>Price/hr</th>
                    <th>Images</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                    <?php
                        $room_id = $room['room_id'];
                        $booking_check = mysqli_query($connect, "SELECT COUNT(*) as cnt FROM bookings WHERE room_id = '$room_id'");
                        $has_booking = mysqli_fetch_assoc($booking_check)['cnt'] > 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                        <td><?php echo htmlspecialchars($room['seats']); ?></td>
                        <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                        <td><?php echo htmlspecialchars($room['p/hr']); ?> EGP</td>
                        <td>
                            <div class="img-container d-flex flex-wrap">
                                <?php
                                if (!empty($room['images'])) {
                                    $imageFiles = explode(',', $room['images']);
                                    foreach ($imageFiles as $img) {
                                        echo '<img src="img/' . htmlspecialchars(trim($img)) . '" width="60" height="60" class="me-1 mb-1" style="object-fit:cover;border-radius:5px;">';
                                    }
                                }
                                ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($room['room_status']); ?></td>
                        <td>
                            <a href="edit_room.php?id=<?php echo $room['room_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php if ($has_booking): ?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="showCannotDeleteAlert()">Delete</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger btn-sm btn-delete-room" data-id="<?php echo $room['room_id']; ?>">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Room Name:</label>
                        <input type="text" name="room_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seats:</label>
                        <input type="number" name="seats" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Type:</label>
                        <select name="type_id" class="form-select" required>
                            <?php 
                            mysqli_data_seek($room_types_result, 0);
                            while ($type = mysqli_fetch_assoc($room_types_result)): ?>
                                <option value="<?php echo $type['type_id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price per Hour (EGP):</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Status:</label>
                        <select name="room_status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="ongoing">Not Available</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Room Images:</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('added') && urlParams.get('added') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Room added successfully.',
            showConfirmButton: false,
            timer: 1800
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
    
    if (urlParams.has('deleted') && urlParams.get('deleted') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Room deleted successfully.',
            showConfirmButton: false,
            timer: 1800
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    document.querySelectorAll('.btn-delete-room').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const roomId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This room will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#A68868',
                cancelButtonColor: '#CDD5DB',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete_room.php?id=${roomId}`;
                }
            });
        });
    });
});

function showCannotDeleteAlert() {
    Swal.fire({
        icon: 'error',
        title: 'Cannot Delete',
        text: 'You cannot delete a room that has active bookings!',
        confirmButtonColor: '#A68868'
    });
}
</script>

</body>
</html>

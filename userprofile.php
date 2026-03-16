<?php
ob_start();
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == "" || $_SESSION['role'] == "admin") {
    header('location:../index.php');
    exit();
}

// Handle profile update
if(isset($_POST['btnsave'])) {
    $username    = trim($_POST['txtname']);
    $useraddress = trim($_POST['txtaddress']);
    $userage     = trim($_POST['txtage']);
    $usercontact = trim($_POST['txtcontact']);
    $userid      = $_SESSION['userid'];

    // Handle image upload
    $imgName = $_SESSION['userimage']; // keep existing by default

    if(isset($_FILES['txtimage']) && $_FILES['txtimage']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['txtimage']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $imgTmp  = $_FILES['txtimage']['tmp_name'];
            $imgName = time() . '_' . basename($_FILES['txtimage']['name']);
            $imgPath = "uploads/" . $imgName;
            if(!is_dir("uploads")) mkdir("uploads", 0777, true);
            move_uploaded_file($imgTmp, $imgPath);
        } else {
            $_SESSION['status']      = "Invalid image format. Use JPG, PNG, or GIF.";
            $_SESSION['status_code'] = "error";
            header("Location: userprofile.php");
            exit();
        }
    }

    try {
        $update = $pdo->prepare("
            UPDATE tbl_user
            SET username=:name, useraddress=:address, userage=:age,
                usercontact=:contact, userimage=:img
            WHERE userid=:id
        ");
        $update->bindParam(':name',    $username);
        $update->bindParam(':address', $useraddress);
        $update->bindParam(':age',     $userage);
        $update->bindParam(':contact', $usercontact);
        $update->bindParam(':img',     $imgName);
        $update->bindParam(':id',      $userid);
        $update->execute();

        // Update session values
        $_SESSION['username']  = $username;
        $_SESSION['userimage'] = $imgName;

        $_SESSION['status']      = "Profile updated successfully!";
        $_SESSION['status_code'] = "success";
    } catch(Exception $e) {
        $_SESSION['status']      = "Failed to update profile.";
        $_SESSION['status_code'] = "error";
    }

    header("Location: userprofile.php");
    exit();
}

// Fetch current user data
$user = null;
try {
    $q = $pdo->prepare("SELECT * FROM tbl_user WHERE userid = :id");
    $q->bindParam(':id', $_SESSION['userid']);
    $q->execute();
    $user = $q->fetch(PDO::FETCH_OBJ);
} catch(Exception $e) {}

include_once "headeruser.php";
?>

<div class="content-wrapper">

    <!-- Page Header -->
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">
                <i class="fas fa-user-circle text-primary"></i> My Profile
            </h1>
            <hr class="mt-2 mb-0">
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">

                <!-- Profile Card (left) -->
                <div class="col-lg-3 col-md-4 col-12 mb-4">
                    <div class="card card-primary card-outline text-center">
                        <div class="card-body pt-4 pb-3">
                            <div class="position-relative d-inline-block mb-3">
                                <img id="previewImg"
                                     src="uploads/<?php echo htmlspecialchars($user->userimage ?? 'default.jpg'); ?>"
                                     class="img-circle elevation-2"
                                     alt="Profile Photo"
                                     style="width:110px;height:110px;object-fit:cover;border:3px solid #007bff;">
                                <label for="txtimage" class="position-absolute" title="Change photo"
                                       style="bottom:0;right:0;background:#007bff;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <h5 class="mb-0 font-weight-bold"><?php echo htmlspecialchars($user->username ?? ''); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($user->useremail ?? ''); ?></small>
                            <hr class="my-2">
                            <span class="badge badge-primary px-3 py-1">
                                <i class="fas fa-user-tag mr-1"></i><?php echo htmlspecialchars($user->role ?? 'User'); ?>
                            </span>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="userchangepassword.php" class="btn btn-outline-secondary btn-sm btn-block">
                                <i class="fas fa-key mr-1"></i> Change Password
                            </a>
                        </div>
                    </div>

                    <!-- Quick Info Card -->
                    <div class="card card-outline card-info">
                        <div class="card-header py-2">
                            <h6 class="card-title m-0"><i class="fas fa-info-circle mr-1 text-info"></i> Quick Info</h6>
                        </div>
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <small class="text-muted"><i class="fas fa-phone mr-1"></i> Contact</small>
                                <small class="font-weight-bold"><?php echo htmlspecialchars($user->usercontact ?? '—'); ?></small>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <small class="text-muted"><i class="fas fa-birthday-cake mr-1"></i> Age</small>
                                <small class="font-weight-bold"><?php echo htmlspecialchars($user->userage ?? '—'); ?></small>
                            </div>
                            <div class="py-1">
                                <small class="text-muted"><i class="fas fa-map-marker-alt mr-1"></i> Address</small><br>
                                <small class="font-weight-bold"><?php echo htmlspecialchars($user->useraddress ?? '—'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Form (right) -->
                <div class="col-lg-7 col-md-8 col-12 mb-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white">
                                <i class="fas fa-edit mr-1"></i> Edit Profile
                            </h3>
                        </div>
                        <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
                            <div class="card-body">

                                <!-- Hidden file input -->
                                <input type="file" name="txtimage" id="txtimage" accept="image/*" class="d-none">

                                <div class="form-row">
                                    <!-- Full Name -->
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-user text-primary mr-1"></i> Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="txtname"
                                               value="<?php echo htmlspecialchars($user->username ?? ''); ?>"
                                               placeholder="Full Name" required>
                                    </div>
                                    <!-- Email (read-only) -->
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-envelope text-primary mr-1"></i> Email</label>
                                        <input type="email" class="form-control bg-light"
                                               value="<?php echo htmlspecialchars($user->useremail ?? ''); ?>"
                                               readonly title="Email cannot be changed">
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <!-- Contact -->
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-phone text-primary mr-1"></i> Contact Number</label>
                                        <input type="text" class="form-control" name="txtcontact"
                                               value="<?php echo htmlspecialchars($user->usercontact ?? ''); ?>"
                                               placeholder="e.g. 09xxxxxxxxx">
                                    </div>
                                    <!-- Age -->
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-birthday-cake text-primary mr-1"></i> Age</label>
                                        <input type="number" class="form-control" name="txtage"
                                               value="<?php echo htmlspecialchars($user->userage ?? ''); ?>"
                                               placeholder="Age" min="1" max="120">
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="form-group">
                                    <label><i class="fas fa-map-marker-alt text-primary mr-1"></i> Address</label>
                                    <textarea class="form-control" name="txtaddress" rows="2"
                                              placeholder="Enter your address"><?php echo htmlspecialchars($user->useraddress ?? ''); ?></textarea>
                                </div>

                                <!-- Profile Photo Upload -->
                                <div class="form-group">
                                    <label><i class="fas fa-camera text-primary mr-1"></i> Profile Photo</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="txtimage_display"
                                                   id="txtimageDisplay" accept="image/*">
                                            <label class="custom-file-label" for="txtimageDisplay">Choose photo...</label>
                                        </div>
                                    </div>
                                    <small class="text-muted">Accepted: JPG, PNG, GIF, WEBP. Leave blank to keep current photo.</small>
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit" name="btnsave" class="btn btn-primary px-4">
                                    <i class="fas fa-save mr-1"></i> Save Changes
                                </button>
                                <a href="userdashboard.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {

    // Sync the two file inputs (camera icon + custom-file-input)
    // When clicking camera icon label, trigger the custom file input
    $('#txtimage').on('change', function() {
        syncFile(this);
    });
    $('#txtimageDisplay').on('change', function() {
        syncFile(this);
        // Update the file label
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName || 'Choose photo...');
    });

    function syncFile(input) {
        if(input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);

            // Copy file to the actual form input
            var dt = new DataTransfer();
            dt.items.add(input.files[0]);
            document.getElementById('txtimage').files = dt.files;
            document.getElementById('txtimageDisplay').files = dt.files;
            $('.custom-file-label').text(input.files[0].name);
        }
    }

});
</script>

<?php if(isset($_SESSION['status']) && $_SESSION['status'] != ''): ?>
<script>
Swal.fire({
    icon: '<?php echo $_SESSION['status_code']; ?>',
    title: '<?php echo $_SESSION['status']; ?>',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php unset($_SESSION['status']); unset($_SESSION['status_code']); endif; ?>

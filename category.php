<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";

if(isset($_POST['btnsave'])) {
    $category = $_POST['txtcategory'];
    $vat      = $_POST['txtvat'];
    if(empty($category)) {
        $_SESSION['status'] = "Category field is empty";
        $_SESSION['status_code'] = "warning";
    } else {
        $insert = $pdo->prepare("INSERT INTO tbl_category (category, vat) VALUES (:cat, :vat)");
        $insert->bindParam(':cat', $category);
        $insert->bindParam(':vat', $vat);
        if ($insert->execute()) {
            $_SESSION['status'] = "Category added successfully";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "Error adding category";
            $_SESSION['status_code'] = "error";
        }
    }
    header('location:category.php');
    exit();
}

if(isset($_POST['btnupdate'])) {
    $category = $_POST['txtcategory'];
    $vat      = $_POST['txtvat'];
    $id       = $_POST['txtcatid'];
    if(empty($category)) {
        $_SESSION['status'] = "Category field is empty";
        $_SESSION['status_code'] = "warning";
    } else {
        $update = $pdo->prepare("UPDATE tbl_category SET category=:cat, vat=:vat WHERE catid=:id");
        $update->bindParam(':cat', $category);
        $update->bindParam(':vat', $vat);
        $update->bindParam(':id',  $id);
        if ($update->execute()) {
            $_SESSION['status'] = "Category updated successfully";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "Category not updated";
            $_SESSION['status_code'] = "error";
        }
    }
    header('location:category.php');
    exit();
}

if(isset($_POST['btndelete'])) {
    $delete = $pdo->prepare("DELETE FROM tbl_category WHERE catid=:catid");
    $delete->bindParam(':catid', $_POST['btndelete']);
    if($delete->execute()) {
        $_SESSION['status'] = "Deleted successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Delete failed";
        $_SESSION['status_code'] = "error";
    }
    header('location:category.php');
    exit();
}
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Category</h1>
                    <hr>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Category</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card card-warning card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="fas fa-tags mr-2"></i>Category List</h5>
                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                        <i class="fas fa-plus mr-1"></i> Add Category
                    </button>
                </div>
                <div class="card-body">
                    <table id="table_category" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>VAT Rate</th>
                                <th>Edit</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $select = $pdo->prepare("SELECT * FROM tbl_category ORDER BY catid ASC");
                            $select->execute();
                            while($row = $select->fetch(PDO::FETCH_OBJ)) {
                                echo '
                                <tr>
                                    <td>'.$row->catid.'</td>
                                    <td>'.$row->category.'</td>
                                    <td><span class="badge badge-info">'.(int)$row->vat.'%</span></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-edit"
                                            data-id="'.$row->catid.'"
                                            data-category="'.htmlspecialchars($row->category).'"
                                            data-vat="'.(int)$row->vat.'"
                                            data-toggle="modal" data-target="#editCategoryModal">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" value="'.$row->catid.'">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>';
                            }
                            ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus-circle mr-2"></i>Add New Category
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i>Category Name</label>
                        <input type="text" class="form-control" name="txtcategory" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-percent mr-1"></i>VAT Rate</label>
                        <select class="form-control" name="txtvat">
                            <option value="0">0%</option>
                            <option value="10">10%</option>
                            <option value="20">20%</option>
                            <option value="30">30%</option>
                            <option value="40">40%</option>
                            <option value="50">50%</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" name="btnsave">
                        <i class="fas fa-save mr-1"></i>Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit mr-2"></i>Edit Category
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="txtcatid" id="edit_catid">
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i>Category Name</label>
                        <input type="text" class="form-control" name="txtcategory" id="edit_category" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-percent mr-1"></i>VAT Rate</label>
                        <select class="form-control" name="txtvat" id="edit_vat">
                            <option value="0">0%</option>
                            <option value="10">10%</option>
                            <option value="20">20%</option>
                            <option value="30">30%</option>
                            <option value="40">40%</option>
                            <option value="50">50%</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" name="btnupdate">
                        <i class="fas fa-save mr-1"></i>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE FORM (hidden) -->
<form id="deleteForm" action="" method="post" style="display:none;">
    <input type="hidden" name="btndelete" id="delete_catid">
</form>

<?php include_once "footer.php"; ?>

<?php if(isset($_SESSION['status']) && $_SESSION['status'] != ''): ?>
<script>
    Swal.fire({
        icon: '<?php echo $_SESSION['status_code']; ?>',
        title: '<?php echo $_SESSION['status']; ?>',
        showConfirmButton: true,
        timer: 2000
    });
</script>
<?php
    unset($_SESSION['status']);
    unset($_SESSION['status_code']);
endif;
?>

<script>
$(document).ready(function() {

    // Initialize DataTable
    $('#table_category').DataTable();

    // Fill edit modal with data
    $('.btn-edit').on('click', function() {
        var id       = $(this).data('id');
        var category = $(this).data('category');
        var vat      = $(this).data('vat');

        $('#edit_catid').val(id);
        $('#edit_category').val(category);
        $('#edit_vat').val(vat);
    });

    // Delete confirmation
    $('.btn-delete').click(function() {
        var catId = $(this).val();
        Swal.fire({
            title: 'Are you sure?',
            text: 'This category will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63032',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete_catid').val(catId);
                $('#deleteForm').submit();
            }
        });
    });

});
</script>

<div style="width: 220px; background-color: #343a40; color: #fff; padding: 20px; min-height: 100vh;">
    
    <ul style="list-style: none; padding: 0;">
        <!-- Categories Dropdown -->
        <li style="margin-bottom: 10px;">
            <a href="add_category.php" class="dropdown-btn" style="color: #adb5bd; text-decoration: none; display: block; padding: 8px 0; font-size: 18px;">Categories</a>
            </li>
        <!-- Products Dropdown -->
        <li style="margin-bottom: 10px;">
            <a href="javascript:void(0)" class="dropdown-btn" style="color: #adb5bd; text-decoration: none; display: block; padding: 8px 0; font-size: 18px;">Products</a>
            <ul class="dropdown-container" style="display: none; list-style: none; padding-left: 15px;">
                <li style="margin-bottom: 12px;">
                    <a href="add_product.php" style="color: #adb5bd; text-decoration: none; font-size: 16px;">Add New Product</a>
                </li>
                <li style="margin-bottom: 12px;">
                    <a href="show_product.php" style="color: #adb5bd; text-decoration: none; font-size: 16px;">Show Products</a>
                </li>
            </ul>
        </li>

        <!-- Sales Dropdown -->
        <li style="margin-bottom: 10px;">
            <a href="javascript:void(0)" class="dropdown-btn" style="color: #adb5bd; text-decoration: none; display: block; padding: 8px 0; font-size: 18px;">Sales</a>
            <ul class="dropdown-container" style="display: none; list-style: none; padding-left: 15px;">
                <li style="margin-bottom: 8px;">
                    <a href="add_sales.php" style="color: #adb5bd; text-decoration: none; font-size: 16px;">Add New Sale</a>
                </li>
                <li style="margin-bottom: 8px;">
                    <a href="show_sales.php" style="color: #adb5bd; text-decoration: none; font-size: 16px;">Show Sales</a>
                </li>
            </ul>
        </li>
                <!-- Warehouse Dropdown -->
                <li style="margin-bottom: 10px;">
            <a href="javascript:void(0)" class="dropdown-btn" style="color: #adb5bd; text-decoration: none; display: block; padding: 8px 0; font-size: 18px;">Warehouse</a>
            <ul class="dropdown-container" style="display: none; list-style: none; padding-left: 15px;">
                <li style="margin-bottom: 8px;">
                    <a href="datawarehouse_frontend.php" style="color: #adb5bd; text-decoration: none; font-size: 16px;">Data Warehouse</a>
                </li>
            </ul>
        </li>
    </ul>
</div>

<!-- Include dropdown toggle script -->
<script>
    // Toggle dropdown menu for sidebar items
    document.addEventListener("DOMContentLoaded", function() {
        var dropdowns = document.getElementsByClassName("dropdown-btn");
        for (var i = 0; i < dropdowns.length; i++) {
            dropdowns[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var dropdownContent = this.nextElementSibling;
                if (dropdownContent.style.display === "block") {
                    dropdownContent.style.display = "none";
                } else {
                    dropdownContent.style.display = "block";
                }
            });
        }
    });
</script>

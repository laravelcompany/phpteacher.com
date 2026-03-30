procedure quick_sort(
    array : list of sortable items,
    start : first element of list,
    end : last element of list
)
    if start < end
        pivot_point ← partition(array, start, end)
        quick_sort(array, start, pivot_point - 1)
        quick_sort(array, pivot_point + 1, end)
    end if
end

procedure partition(
    array : list of sortable items, 
    start : first element of list,
    end : last element of list
)
    mid ← (start + end) / 2
    swap array[start] and array[mid]

    pivot_index ← start
    pivot_value ← array[start]

    scan ← start + 1
    while scan <= end
        if array[scan] < pivot_value
            pivot_index ← pivot_index + 1
            swap array[pivot_index] and array[scan]
        end if
        scan ← scan + 1
    end while

    swap array[start] and array[pivot_index]

    return pivot_index
end procedure
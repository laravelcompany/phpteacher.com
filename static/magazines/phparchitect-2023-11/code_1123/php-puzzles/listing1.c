# Start with the largest gap and work down to a gap of
# 1 similar to insertion sort but instead of 1, gap is
# being used in each step
foreach (gap in gaps)
{
  # Do a gapped insertion sort for every elements in
  # gaps.  Each loop leaves a[0..gap-1] in gapped order
  for (i = gap; i < n; i += 1)
  {
    # save a[i] in temp and make a hole at position i
    temp = a[i]
    # shift earlier gap-sorted elements up until the
    # correct location for a[i] is found
    for (j=i; (j>=gap) && (a[j - gap]>temp); j -= gap)
    {
      a[j] = a[j - gap]
    }
    # put temp (original a[i]) in its correct location
    a[j] = temp
  }
}
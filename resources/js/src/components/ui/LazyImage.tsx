import { useEffect, useRef, useState, type CSSProperties, type ImgHTMLAttributes } from "react";

type LazyImageProps = Omit<ImgHTMLAttributes<HTMLImageElement>, "className" | "style"> & {
  className?: string;
  wrapperClassName?: string;
  skeletonClassName?: string;
  wrapperStyle?: CSSProperties;
  style?: CSSProperties;
  showSkeleton?: boolean;
};

export function LazyImage({
  alt,
  className = "",
  decoding,
  fetchPriority,
  loading,
  onError,
  onLoad,
  showSkeleton = true,
  skeletonClassName = "",
  src,
  style,
  wrapperClassName = "",
  wrapperStyle,
  ...rest
}: LazyImageProps) {
  const imageRef = useRef<HTMLImageElement | null>(null);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    setLoaded(false);
  }, [src]);

  useEffect(() => {
    const image = imageRef.current;

    if (image?.complete && image.naturalWidth > 0) {
      setLoaded(true);
    }
  }, [src]);

  const effectiveLoading = loading ?? (fetchPriority === "high" ? "eager" : "lazy");

  return (
    <div className={`relative ${wrapperClassName}`.trim()} style={wrapperStyle}>
      {showSkeleton && !loaded ? (
        <div
          aria-hidden="true"
          className={`landing-skeleton absolute inset-0 ${skeletonClassName}`.trim()}
        />
      ) : null}

      <img
        {...rest}
        ref={imageRef}
        alt={alt}
        decoding={decoding ?? "async"}
        fetchPriority={fetchPriority}
        loading={effectiveLoading}
        onError={(event) => {
          setLoaded(true);
          onError?.(event);
        }}
        onLoad={(event) => {
          setLoaded(true);
          onLoad?.(event);
        }}
        src={src}
        className={`${className} transition-opacity duration-500 ease-out ${
          loaded ? "opacity-100" : "opacity-0"
        }`.trim()}
        style={style}
      />
    </div>
  );
}

import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  // Allow H5P tours exported as HTML (public/h5p) to be shown in an iframe on our own pages.
  async headers() {
    return [{ source: '/h5p/:path*', headers: [{ key: 'X-Frame-Options', value: 'SAMEORIGIN' }] }];
  },
};

export default nextConfig;
